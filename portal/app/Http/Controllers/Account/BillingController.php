<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Services\Billing;
use App\Services\StripeEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingController extends Controller
{
    public function pay(Request $request, Billing $billing)
    {
        $shop = $request->user()->shop;
        if ($shop->setup_paid_at) {
            return redirect()->route('dashboard');
        }

        try {
            return redirect()->away($billing->checkoutUrl($shop, $request->user(), route('paid'), route('dashboard')));
        } catch (Throwable $e) {
            Log::error('Checkout failed for shop '.$shop->id.': '.$e->getMessage());

            return redirect()->route('dashboard')->with('error', 'We couldn\'t open the payment page just now. Please try again in a minute, or email hello@3dplatebuilder.com.');
        }
    }

    public function paid(Request $request, Billing $billing, StripeEvents $events)
    {
        $shop = $request->user()->shop->fresh();

        // Backup for a slow webhook: read the Checkout Session ourselves when they come back.
        $sessionId = (string) $request->query('session_id');
        if (! $shop->setup_paid_at && str_starts_with($sessionId, 'cs_')) {
            try {
                $s = $billing->checkoutSession($sessionId);
                if ((string) ($s['metadata']['shop_id'] ?? '') === (string) $shop->id && ($s['payment_status'] ?? '') === 'paid') {
                    $events->checkoutCompleted($s);
                    $shop->refresh();
                }
            } catch (Throwable $e) {
                Log::warning('Could not confirm Checkout Session on return: '.$e->getMessage());
            }
        }

        if ($shop->setup_paid_at) {
            return redirect()->route('dashboard')->with('status', 'Payment received. Thank you! Here\'s what to do next.');
        }

        return view('account.paid');
    }

    public function index(Request $request)
    {
        $shop = $request->user()->shop;

        return view('account.billing', [
            'shop' => $shop,
            'payments' => $shop->payments()->whereNotIn('status', ['draft', 'void'])->latest('id')->get(),
            'addons' => $shop->addonRequests()->latest()->get(),
        ]);
    }

    public function portal(Request $request, Billing $billing)
    {
        try {
            return redirect()->away($billing->portalUrl($request->user()->shop, route('billing')));
        } catch (Throwable $e) {
            Log::error('Billing portal failed: '.$e->getMessage());

            return back()->with('error', 'We couldn\'t open your billing page just now. Please try again shortly.');
        }
    }
}
