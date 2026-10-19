<?php

namespace App\Http\Controllers;

use App\Services\Billing;
use App\Services\StripeEvents;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class StripeWebhookController extends Controller
{
    public function __invoke(Request $request, Billing $billing, StripeEvents $events)
    {
        try {
            $event = $billing->verifiedEvent($request->getContent(), $request->header('Stripe-Signature'));
        } catch (Throwable $e) {
            Log::warning('Stripe webhook rejected: '.$e->getMessage());

            return response('Invalid signature', 400);
        }

        $events->handle($event);

        return response('ok');
    }
}
