<?php

namespace Tests;

use App\Models\Shop;
use App\Models\User;
use App\Services\Billing;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\FakeBilling;

abstract class TestCase extends BaseTestCase
{
    protected FakeBilling $billing;

    protected function setUp(): void
    {
        parent::setUp();
        $this->billing = new FakeBilling;
        $this->app->instance(Billing::class, $this->billing);
    }

    protected function customer(array $shop = [], bool $paid = true): User
    {
        $s = Shop::create(array_merge([
            'name' => 'Your Plates Ltd',
            'plan' => 'pro',
            'stage' => $paid ? 'onboarding' : 'not_paid',
            'setup_paid_at' => $paid ? now() : null,
            'stripe_customer_id' => $paid ? 'cus_test1' : null,
        ], $shop));

        return User::factory()->create(['shop_id' => $s->id, 'email' => 'owner'.$s->id.'@yourplates.test']);
    }

    protected function admin(): User
    {
        $u = User::factory()->create(['email' => 'admin'.uniqid().'@3dplatebuilder.test']);
        $u->is_admin = true;
        $u->save();

        return $u;
    }

    protected function webhook(array $event)
    {
        return $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'valid', 'CONTENT_TYPE' => 'application/json'], json_encode($event));
    }
}
