<?php

namespace App\Services;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Everything the portal asks of Stripe. StripeBilling talks to Stripe; tests swap in a fake.
 *
 * How plate makers pay:
 *  1. Sign-up: one Checkout payment for the set-up fee plus the first month. The card is saved.
 *  2. Go-live: the monthly subscription starts on the saved card. The first month is already paid,
 *     so its first charge is one month after go-live, then monthly.
 */
interface Billing
{
    /** A Stripe Checkout page for the set-up fee and the first month. Returns the page's URL. */
    public function checkoutUrl(Shop $shop, User $user, string $successUrl, string $cancelUrl): string;

    /** The card the customer paid with at sign-up, made their default for later charges. Returns its id. */
    public function saveCardFromPayment(string $customerId, string $paymentIntentId): ?string;

    /**
     * Starts the monthly subscription on the saved card, first charged on $firstCharge.
     * Returns ['id' => …, 'status' => …, 'current_period_end' => timestamp|null].
     */
    public function startSubscription(Shop $shop, Carbon $firstCharge): array;

    /** Stripe's customer portal (card, invoices, billing details). Returns its URL. */
    public function portalUrl(Shop $shop, string $returnUrl): string;

    /** Bills a one-off amount now on its own invoice, charged to the saved card. Returns the invoice item id. */
    public function addInvoiceItem(Shop $shop, string $description, int $pence): string;

    /** Checks the webhook signature and returns the event as an array, or throws. */
    public function verifiedEvent(string $payload, ?string $signature): array;
}
