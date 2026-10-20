<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\CustomerEmailTest;
use App\Support\EmailSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/** Customer emails: Starter sees what it sends; Pro and Complete write their own order email and review request. */
class EmailsController extends Controller
{
    public function edit(Request $request)
    {
        $shop = $request->user()->shop;

        return view('account.emails', ['shop' => $shop, 'e' => EmailSettings::forShop($shop)]);
    }

    public function update(Request $request)
    {
        $shop = $request->user()->shop;
        abort_unless($shop->hasCustomerEmails(), 403);

        $data = $request->validate(EmailSettings::rules(), [
            'review_url.url' => 'Paste the full link to your review page, starting https://',
            'review_url.required_if_accepted' => 'Add the link to your review page, or switch the review email off.',
            'order_heading.required_if_accepted' => 'Give the order email a heading, or switch it off.',
            'review_heading.required_if_accepted' => 'Give the review email a heading, or switch it off.',
        ]);
        $clean = ['order_on' => (bool) ($data['order_on'] ?? false), 'review_on' => (bool) ($data['review_on'] ?? false)];
        foreach (array_keys(EmailSettings::FIELDS) as $key) {
            $clean[$key] = trim((string) ($data[$key] ?? ''));
        }
        $shop->update(['emails' => $clean]);

        if ($request->input('test')) {
            $which = $request->input('test') === 'review' ? 'review' : 'order';
            Mail::to($request->user()->email)->send(new CustomerEmailTest($shop, $which, EmailSettings::forShop($shop->fresh())));

            return back()->with('status', "Saved, and a test is on its way to {$request->user()->email}.");
        }

        return back()->with('status', 'Saved. Publish when you\'re ready for your website to use it.');
    }
}
