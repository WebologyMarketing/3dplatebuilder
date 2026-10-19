<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;

/**
 * Everything the portal asks of Stripe. StripeBilling talks to Stripe; tests swap in a fake.
 */
interface Billing
{
    /** A Stripe Checkout page for the plan's monthly price plus its set-up fee. Returns the page's URL. */
    public function checkoutUrl(Shop $shop, User $user, string $successUrl, string $cancelUrl): string;

    /** Stripe's customer portal (card, invoices, billing details). Returns its URL. */
    public function portalUrl(Shop $shop, string $returnUrl): string;

    /** Adds a one-off line to the customer's next invoice. Returns the invoice item id. */
    public function addInvoiceItem(Shop $shop, string $description, int $pence): string;

    /** Checks the webhook signature and returns the event as an array, or throws. */
    public function verifiedEvent(string $payload, ?string $signature): array;

    /** Re-reads a subscription, for its status and period end. */
    public function subscription(string $id): array;
}
