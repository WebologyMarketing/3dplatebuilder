<?php

namespace App\Services;

use App\Mail\PortalNotice;
use App\Models\Payment;
use App\Models\Shop;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Keeps shops and payments in step with Stripe. Each event is handled once (stripe_events).
 */
class StripeEvents
{
    public function __construct(private Billing $billing) {}

    public function handle(array $event): void
    {
        $id = $event['id'] ?? null;
        if (! $id || DB::table('stripe_events')->where('id', $id)->exists()) {
            return;
        }

        $object = $event['data']['object'] ?? [];

        match ($event['type'] ?? '') {
            'checkout.session.completed' => $this->checkoutCompleted($object),
            'customer.subscription.created', 'customer.subscription.updated' => $this->subscriptionChanged($object),
            'customer.subscription.deleted' => $this->subscriptionEnded($object),
            'invoice.created', 'invoice.finalized', 'invoice.updated', 'invoice.paid',
            'invoice.payment_failed', 'invoice.voided', 'invoice.marked_uncollectible' => $this->invoice($object, $event['type']),
            default => null,
        };

        DB::table('stripe_events')->insert(['id' => $id, 'type' => $event['type'] ?? '', 'created_at' => now(), 'updated_at' => now()]);
    }

    /** Also called when the customer lands back on the portal, so a slow webhook can't leave them waiting. */
    public function checkoutCompleted(array $s): void
    {
        $shop = Shop::find($s['metadata']['shop_id'] ?? $s['client_reference_id'] ?? 0);
        if (! $shop) {
            return;
        }

        $shop->stripe_customer_id = $s['customer'] ?? $shop->stripe_customer_id;

        if (($s['payment_status'] ?? '') === 'paid' && ! $shop->setup_paid_at) {
            $shop->setup_paid_at = now();
            $shop->subscription_status = 'awaiting_go_live';
            // Keep the card they paid with for the monthly payments that start at go-live.
            if (! empty($s['payment_intent']) && $shop->stripe_customer_id) {
                try {
                    $shop->stripe_payment_method_id = $this->billing->saveCardFromPayment($shop->stripe_customer_id, $s['payment_intent']);
                } catch (Throwable $e) {
                    Log::warning('Could not save the card for shop '.$shop->id.': '.$e->getMessage());
                }
            }
            if ($shop->stage === 'not_paid') {
                $shop->stage = 'onboarding';
            }
            $shop->licence_key ??= Shop::newLicenceKey();
            $shop->save();

            Mail::to(config('portal.admin_email'))->send(new PortalNotice(
                "New shop paid: {$shop->name}",
                ["{$shop->name} has paid for the {$shop->planName()} plan, and can now start onboarding."],
                route('admin.shops.show', $shop),
                'Open their account',
            ));
            foreach ($shop->users as $user) {
                Mail::to($user->email)->send(new PortalNotice(
                    'Thanks, your payment went through',
                    ["You're on the {$shop->planName()} plan. We've taken your set-up fee and your first month.", 'Your monthly payments start when your shop goes live: the first is taken a month after that day, from the card you just used.', 'Your next step is to add your details and logo, then set your prices and publish, so we can build your shop.'],
                    route('dashboard'),
                    'Carry on with set-up',
                ));
            }

            return;
        }

        $shop->save();
    }

    private function subscriptionChanged(array $sub): void
    {
        $shop = $this->shopFor($sub['metadata']['shop_id'] ?? null, $sub['customer'] ?? null);
        if (! $shop) {
            return;
        }
        $end = $sub['current_period_end'] ?? ($sub['items']['data'][0]['current_period_end'] ?? null);
        $shop->fill([
            'stripe_subscription_id' => $sub['id'],
            'subscription_status' => $sub['status'] ?? null,
            'current_period_end' => $end ? Carbon::createFromTimestamp($end) : $shop->current_period_end,
        ])->save();
    }

    private function subscriptionEnded(array $sub): void
    {
        $shop = $this->shopFor($sub['metadata']['shop_id'] ?? null, $sub['customer'] ?? null);
        if (! $shop) {
            return;
        }
        $shop->fill(['subscription_status' => 'canceled', 'stage' => 'cancelled', 'cancelled_at' => now()])->save();
        Mail::to(config('portal.admin_email'))->send(new PortalNotice(
            "Subscription ended: {$shop->name}",
            ["Stripe has ended {$shop->name}'s subscription. Their shop is marked as cancelled in the portal."],
            route('admin.shops.show', $shop),
            'Open their account',
        ));
    }

    private function invoice(array $inv, string $type): void
    {
        $shop = $this->shopFor(
            $inv['parent']['subscription_details']['metadata']['shop_id'] ?? ($inv['subscription_details']['metadata']['shop_id'] ?? null),
            $inv['customer'] ?? null,
        );

        $lines = collect($inv['lines']['data'] ?? [])->pluck('description')->filter()->implode(', ');
        $due = $inv['due_date'] ?? null;
        if (! $due && ($inv['status'] ?? '') === 'open') {
            // Card payments have no due date: they're due when Stripe first tries the card.
            $due = $inv['status_transitions']['finalized_at'] ?? ($inv['created'] ?? null);
        }

        $payment = Payment::updateOrCreate(
            ['stripe_invoice_id' => $inv['id']],
            [
                'shop_id' => $shop?->id,
                'number' => $inv['number'] ?? null,
                'description' => mb_strimwidth($lines ?: 'Invoice', 0, 250, '…'),
                'amount_pence' => (int) ($inv['amount_due'] ?? $inv['total'] ?? 0),
                'amount_paid_pence' => (int) ($inv['amount_paid'] ?? 0),
                'status' => $inv['status'] ?? 'draft',
                'attempt_count' => (int) ($inv['attempt_count'] ?? 0),
                'due_at' => $due ? Carbon::createFromTimestamp($due) : null,
                'paid_at' => ! empty($inv['status_transitions']['paid_at']) ? Carbon::createFromTimestamp($inv['status_transitions']['paid_at']) : null,
                'hosted_url' => $inv['hosted_invoice_url'] ?? null,
            ],
        );

        if ($type === 'invoice.payment_failed' && $shop) {
            Mail::to(config('portal.admin_email'))->send(new PortalNotice(
                "Payment failed: {$shop->name}",
                ['A payment of £'.number_format($payment->amount_pence / 100, 2)." from {$shop->name} failed. Stripe will retry it automatically."],
                route('admin.payments'),
                'See payments',
            ));
        }
    }

    private function shopFor(?string $shopId, ?string $customer): ?Shop
    {
        if ($shopId && ($shop = Shop::find($shopId))) {
            return $shop;
        }

        return $customer ? Shop::where('stripe_customer_id', $customer)->first() : null;
    }
}
