<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
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
        if (empty($plan['stripe_monthly']) || empty($plan['stripe_setup'])) {
            throw new RuntimeException("Stripe prices for the {$shop->plan} plan are not set in .env.");
        }

        $params = [
            'mode' => 'subscription',
            'line_items' => [
                ['price' => $plan['stripe_monthly'], 'quantity' => 1],
                ['price' => $plan['stripe_setup'], 'quantity' => 1],
            ],
            'client_reference_id' => (string) $shop->id,
            'metadata' => ['shop_id' => (string) $shop->id, 'plan' => $shop->plan],
            'subscription_data' => ['metadata' => ['shop_id' => (string) $shop->id, 'plan' => $shop->plan]],
            'billing_address_collection' => 'required',
            'tax_id_collection' => ['enabled' => true],
            'allow_promotion_codes' => false,
            'success_url' => $successUrl.(str_contains($successUrl, '?') ? '&' : '?').'session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $cancelUrl,
        ];
        // Not VAT registered yet, so no automatic_tax.
        if ($shop->stripe_customer_id) {
            $params['customer'] = $shop->stripe_customer_id;
            $params['customer_update'] = ['name' => 'auto', 'address' => 'auto'];
        } else {
            $params['customer_email'] = $user->email;
        }

        return $this->stripe()->checkout->sessions->create($params)->url;
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
        $item = $this->stripe()->invoiceItems->create([
            'customer' => $shop->stripe_customer_id,
            'currency' => 'gbp',
            'amount' => $pence,
            'description' => $description,
            'metadata' => ['shop_id' => (string) $shop->id],
        ]);

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

    public function subscription(string $id): array
    {
        return $this->stripe()->subscriptions->retrieve($id)->toArray();
    }
}
