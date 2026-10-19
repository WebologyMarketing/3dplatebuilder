<?php

namespace Tests\Support;

use App\Models\Shop;
use App\Models\User;
use App\Services\Billing;
use RuntimeException;

class FakeBilling implements Billing
{
    public array $checkouts = [];

    public array $items = [];

    public function checkoutUrl(Shop $shop, User $user, string $successUrl, string $cancelUrl): string
    {
        $this->checkouts[] = $shop->id;

        return 'https://checkout.stripe.test/c/'.$shop->id;
    }

    public function portalUrl(Shop $shop, string $returnUrl): string
    {
        return 'https://billing.stripe.test/p/'.$shop->stripe_customer_id;
    }

    public function addInvoiceItem(Shop $shop, string $description, int $pence): string
    {
        $this->items[] = [$shop->id, $description, $pence];

        return 'ii_test_'.count($this->items);
    }

    public function verifiedEvent(string $payload, ?string $signature): array
    {
        if ($signature !== 'valid') {
            throw new RuntimeException('bad signature');
        }

        return json_decode($payload, true);
    }

    public function subscription(string $id): array
    {
        return ['id' => $id, 'status' => 'active'];
    }
}
