<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function index(Request $request)
    {
        return view('account.support', [
            'tickets' => $request->user()->shop->tickets()->latest('updated_at')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);
        $shop = $request->user()->shop;

        $ticket = $shop->tickets()->create(['subject' => $data['subject'], 'status' => 'new', 'last_reply_at' => now()]);
        $ticket->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);

        Mail::to(config('portal.admin_email'))->send(new PortalNotice(
            "New ticket from {$shop->name}: {$ticket->subject}",
            [$data['body']],
            route('admin.tickets.show', $ticket),
            'Reply',
        ));

        return redirect()->route('support.show', $ticket)->with('status', 'Sent. We\'ll reply by email and here.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->shop_id === $request->user()->shop_id, 404);

        return view('account.ticket', ['ticket' => $ticket->load('messages.user')]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->shop_id === $request->user()->shop_id, 404);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);

        $ticket->messages()->create(['user_id' => $request->user()->id, 'body' => $data['body']]);
        $ticket->update(['status' => 'waiting_on_us', 'last_reply_at' => now()]);

        Mail::to(config('portal.admin_email'))->send(new PortalNotice(
            "Reply from {$ticket->shop->name}: {$ticket->subject}",
            [$data['body']],
            route('admin.tickets.show', $ticket),
            'Reply',
        ));

        return back()->with('status', 'Reply sent.');
    }
}
