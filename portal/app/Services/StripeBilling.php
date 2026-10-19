<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;
use RuntimeException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripeBilling implements Billing
{
    private ?StripeClient $client = null;

    private function stripe(): StripeClient
    {
        $secret = config('portal.stripe.secret');
        if (! $secret) {
            throw new RuntimeException('STRIPE_SECRET is not set in .env.');
        }

        return $this->client ??= new StripeClient($secret);
    }

    public function checkoutUrl(Shop $shop, User $user, string $successUrl, string $cancelUrl): string
    {
        $plan = $shop->planConfig();
        if (empty($plan['stripe_setup']) || empty($plan['monthly'])) {
            throw new RuntimeException("Stripe prices for the {$shop->plan} plan are not set in .env.");
        }

        $params = [
            'mode' => 'payment',
            'line_items' => [
                ['price' => $plan['stripe_setup'], 'quantity' => 1],
                // The first month, paid now; the subscription that follows starts at go-live.
                ['price_data' => [
                    'currency' => 'gbp',
                    'unit_amount' => $plan['monthly'],
                    'product_data' => ['name' => $plan['name'].' plan, first month (starts when your shop goes live)'],
                ], 'quantity' => 1],
            ],
            'client_reference_id' => (string) $shop->id,
            'metadata' => ['shop_id' => (string) $shop->id, 'plan' => $shop->plan],
            'payment_intent_data' => [
                'setup_future_usage' => 'off_session', // keeps the card for the monthly payments
                'metadata' => ['shop_id' => (string) $shop->id],
                'description' => '3dPlateBuilder.com '.$plan['name'].': set-up fee and first month',
            ],
            'invoice_creation' => ['enabled' => true, 'invoice_data' => ['metadata' => ['shop_id' => (string) $shop->id]]],
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => true],
            'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ];
        // Not VAT registered yet, so no automatic_tax.
        if ($shop->stripe_customer_id) {
            $params['customer'] = $shop->stripe_customer_id;
            $params['customer_update'] = ['name' => 'auto', 'address' => 'auto'];
        } else {
            $params['customer_creation'] = 'always';
            $params['customer_email'] = $user->email;
        }

        return $this->stripe()->checkout->sessions->create($params)->url;
    }

    public function saveCardFromPayment(string $customerId, string $paymentIntentId): ?string
    {
        $pi = $this->stripe()->paymentIntents->retrieve($paymentIntentId);
        $pm = is_string($pi->payment_method) ? $pi->payment_method : ($pi->payment_method->id ?? null);
        if ($pm) {
            $this->stripe()->customers->update($customerId, ['invoice_settings' => ['default_payment_method' => $pm]]);
        }

        return $pm;
    }

    public function startSubscription(Shop $shop, Carbon $firstCharge): array
    {
        $plan = $shop->planConfig();
        if (empty($plan['stripe_monthly'])) {
            throw new RuntimeException("The Stripe monthly price for the {$shop->plan} plan is not set in .env.");
        }
        if (! $shop->stripe_customer_id) {
            throw new RuntimeException('This shop has no Stripe customer yet.');
        }

        $params = [
            'customer' => $shop->stripe_customer_id,
            'items' => [['price' => $plan['stripe_monthly']]],
            // The first month was paid at sign-up: nothing is charged until $firstCharge, then monthly.
            'billing_cycle_anchor' => $firstCharge->timestamp,
            'proration_behavior' => 'none',
            'collection_method' => 'charge_automatically',
            'metadata' => ['shop_id' => (string) $shop->id, 'plan' => $shop->plan],
        ];
        if ($shop->stripe_payment_method_id) {
            $params['default_payment_method'] = $shop->stripe_payment_method_id;
        }

        $sub = $this->stripe()->subscriptions->create($params, ['idempotency_key' => 'start-sub-shop-'.$shop->id]);

        return [
            'id' => $sub->id,
            'status' => $sub->status,
            'current_period_end' => $sub->current_period_end ?? ($sub->items->data[0]->current_period_end ?? null),
        ];
    }

    public function portalUrl(Shop $shop, string $returnUrl): string
    {
        if (! $shop->stripe_customer_id) {
            throw new RuntimeException('This shop has no Stripe customer yet.');
        }

        return $this->stripe()->billingPortal->sessions->create([
            'customer' => $shop->stripe_customer_id,
            'return_url' => $returnUrl,
        ])->url;
    }

    public function addInvoiceItem(Shop $shop, string $description, int $pence): string
    {
        // Billed straight away on its own invoice, charged to the saved card.
        $invoice = $this->stripe()->invoices->create([
            'customer' => $shop->stripe_customer_id,
            'collection_method' => 'charge_automatically',
            'pending_invoice_items_behavior' => 'exclude',
            'auto_advance' => true,
            'metadata' => ['shop_id' => (string) $shop->id],
        ]);
        $item = $this->stripe()->invoiceItems->create([
            'customer' => $shop->stripe_customer_id,
            'invoice' => $invoice->id,
            'currency' => 'gbp',
            'amount' => $pence,
            'description' => $description,
            'metadata' => ['shop_id' => (string) $shop->id],
        ]);
        $this->stripe()->invoices->finalizeInvoice($invoice->id, ['auto_advance' => true]);

        return $item->id;
    }

    public function verifiedEvent(string $payload, ?string $signature): array
    {
        $secret = config('portal.stripe.webhook_secret');
        if (! $secret) {
            throw new RuntimeException('STRIPE_WEBHOOK_SECRET is not set in .env.');
        }

        return Webhook::constructEvent($payload, (string) $signature, $secret)->toArray();
    }
}
