<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Account\DetailsController;
use App\Http\Controllers\Controller;
use App\Mail\PortalNotice;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        return view('admin.documents', [
            'pending' => Document::with('shop')->where('status', 'pending')->oldest()->get(),
            'reviewed' => Document::with('shop')->where('status', '!=', 'pending')->latest('reviewed_at')->limit(30)->get(),
        ]);
    }

    public function file(Document $document)
    {
        abort_unless(Storage::disk('local')->exists($document->path), 404);

        return DetailsController::safeFile($document->path);
    }

    public function review(Request $request, Document $document)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:1000', 'required_if:decision,rejected'],
        ], ['note.required_if' => 'Say why, so they know what to upload instead.']);

        $document->update(['status' => $data['decision'], 'note' => $data['note'] ?? null, 'reviewed_at' => now()]);

        $shop = $document->shop;
        foreach ($shop->users as $user) {
            Mail::to($user->email)->send(new PortalNotice(
                $data['decision'] === 'approved' ? 'Your RNPS registration is approved' : 'We need another copy of your RNPS registration',
                $data['decision'] === 'approved'
                    ? ['Thanks. We\'ve checked your RNPS registration and it\'s all fine.']
                    : ['We couldn\'t accept the document you uploaded.', 'Reason: '.$data['note'], 'Please upload it again from your account.'],
                route('details').'#docs',
                'Open your account',
            ));
        }

        return back()->with('status', $data['decision'] === 'approved' ? "Approved. {$shop->name} has been told." : "Rejected. {$shop->name} has been told why.");
    }
}
