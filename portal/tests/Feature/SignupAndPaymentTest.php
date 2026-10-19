<?php

namespace Tests\Feature;

use App\Mail\PortalNotice;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SignupAndPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_page_shows_the_plans_and_no_vat(): void
    {
        $this->get('/signup?plan=complete')
            ->assertOk()
            ->assertSee('£399 a month')
            ->assertSee('no VAT to add')
            ->assertDontSee('exclude VAT');
    }

    public function test_signing_up_makes_an_account_and_goes_to_payment(): void
    {
        Mail::fake();

        $this->post('/signup', [
            'plan' => 'starter', 'business' => 'North Road Plates', 'name' => 'Sam Patel',
            'email' => 'sam@northroad.test', 'password' => 'a-long-password', 'password_confirmation' => 'a-long-password', 'terms' => '1',
        ])->assertRedirect(route('pay'));

        $user = User::where('email', 'sam@northroad.test')->first();
        $this->assertNotNull($user);
        $this->assertSame('starter', $user->shop->plan);
        $this->assertSame('not_paid', $user->shop->stage);
        $this->assertAuthenticatedAs($user);
        Mail::assertSent(PortalNotice::class, fn ($m) => $m->hasTo(config('portal.admin_email')));

        $this->get(route('pay'))->assertRedirect('https://checkout.stripe.test/c/'.$user->shop_id);
    }

    public function test_signup_needs_terms_and_a_unique_email(): void
    {
        $this->customer();
        $this->post('/signup', ['plan' => 'pro', 'business' => 'X', 'name' => 'Y', 'email' => 'owner1@yourplates.test', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertSessionHasErrors(['email', 'password', 'terms']);
    }

    public function test_set_up_pages_stay_shut_until_paid(): void
    {
        $user = $this->customer(paid: false);
        $this->actingAs($user)->get(route('details'))->assertRedirect(route('dashboard'));
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('Pay your set-up fee to begin');
    }

    public function test_paid_checkout_opens_onboarding_and_makes_a_licence_key_once(): void
    {
        Mail::fake();
        $user = $this->customer(paid: false);
        $event = ['id' => 'evt_1', 'type' => 'checkout.session.completed', 'data' => ['object' => [
            'id' => 'cs_1', 'customer' => 'cus_new', 'subscription' => 'sub_1', 'payment_status' => 'paid',
            'metadata' => ['shop_id' => (string) $user->shop_id],
        ]]];

        $this->webhook($event)->assertOk();
        $this->webhook($event)->assertOk(); // Stripe can send an event twice.

        $shop = $user->shop->fresh();
        $this->assertNotNull($shop->setup_paid_at);
        $this->assertSame('onboarding', $shop->stage);
        $this->assertSame('cus_new', $shop->stripe_customer_id);
        $this->assertMatchesRegularExpression('/^[A-Z0-9]{5}(-[A-Z0-9]{5}){3}$/', $shop->licence_key);
        Mail::assertSent(PortalNotice::class, 2); // you, and them; not again for the repeat

        $this->actingAs($user)->get(route('paid'))->assertRedirect(route('dashboard'));
    }

    public function test_webhook_rejects_a_bad_signature(): void
    {
        $this->call('POST', '/stripe/webhook', [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'nope'], '{}')->assertStatus(400);
    }

    public function test_failed_invoice_shows_as_overdue_and_cancellation_is_recorded(): void
    {
        Mail::fake();
        $user = $this->customer();
        $this->webhook(['id' => 'evt_2', 'type' => 'invoice.payment_failed', 'data' => ['object' => [
            'id' => 'in_1', 'customer' => 'cus_test1', 'amount_due' => 19900, 'amount_paid' => 0, 'status' => 'open',
            'attempt_count' => 2, 'created' => now()->subDays(9)->timestamp,
            'status_transitions' => ['finalized_at' => now()->subDays(9)->timestamp],
            'lines' => ['data' => [['description' => '1 × Pro plan (at £199.00 / month)']]],
        ]]])->assertOk();

        $p = Payment::first();
        $this->assertTrue($p->isOverdue());
        $this->assertSame(19900, $user->shop->overdueBalancePence());

        $this->actingAs($this->admin())->get(route('admin.dashboard'))->assertOk()->assertSee('£199')->assertSee('Your Plates Ltd');

        $this->webhook(['id' => 'evt_3', 'type' => 'customer.subscription.deleted', 'data' => ['object' => [
            'id' => 'sub_1', 'customer' => 'cus_test1', 'status' => 'canceled', 'metadata' => [],
        ]]])->assertOk();
        $this->assertSame('cancelled', $user->shop->fresh()->stage);
    }

    public function test_subscription_update_saves_the_next_payment_date(): void
    {
        $user = $this->customer();
        $end = now()->addMonth()->startOfDay()->timestamp;
        $this->webhook(['id' => 'evt_4', 'type' => 'customer.subscription.updated', 'data' => ['object' => [
            'id' => 'sub_9', 'customer' => 'cus_test1', 'status' => 'active', 'metadata' => ['shop_id' => (string) $user->shop_id],
            'items' => ['data' => [['current_period_end' => $end]]],
        ]]])->assertOk();

        $shop = Shop::find($user->shop_id);
        $this->assertSame($end, $shop->current_period_end->timestamp);
        $this->assertSame('sub_9', $shop->stripe_subscription_id);
    }
}
