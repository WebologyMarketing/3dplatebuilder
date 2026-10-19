<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $show = $request->query('show', 'open');
        $tickets = Ticket::with('shop')
            ->when($show === 'open', fn ($q) => $q->where('status', '!=', 'closed'))
            ->when($show === 'closed', fn ($q) => $q->where('status', 'closed'))
            ->orderByRaw("case status when 'new' then 0 when 'waiting_on_us' then 1 when 'waiting_on_them' then 2 else 3 end")
            ->latest('last_reply_at')
            ->get();

        return view('admin.tickets', compact('tickets', 'show'));
    }

    public function show(Ticket $ticket)
    {
        return view('admin.ticket', ['ticket' => $ticket->load('messages.user', 'shop')]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:5000', 'required_without:close'],
            'close' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['body'])) {
            $ticket->messages()->create(['user_id' => $request->user()->id, 'from_admin' => true, 'body' => $data['body']]);
        }
        $ticket->update([
            'status' => $request->boolean('close') ? 'closed' : 'waiting_on_them',
            'last_reply_at' => now(),
        ]);

        if (! empty($data['body'])) {
            foreach ($ticket->shop->users as $user) {
                Mail::to($user->email)->send(new PortalNotice(
                    'Re: '.$ticket->subject,
                    [$data['body']],
                    route('support.show', $ticket),
                    'Reply in your account',
                ));
            }
        }

        return $request->boolean('close')
            ? redirect()->route('admin.tickets')->with('status', 'Ticket closed.')
            : back()->with('status', 'Reply sent.');
    }
}
