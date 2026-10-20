<?php

namespace Tests\Feature;

use App\Mail\CustomerEmailTest;
use App\Support\SiteConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CustomerEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_starter_sees_the_standard_emails_and_cannot_save(): void
    {
        $user = $this->customer(['plan' => 'starter']);
        $this->actingAs($user)->get(route('emails'))->assertOk()->assertSee('order confirmation')->assertDontSee('Review request');
        $this->actingAs($user)->post(route('emails'), ['order_on' => '1', 'order_heading' => 'Hi'])->assertForbidden();
        $this->assertNull(SiteConfig::build($user->shop)['emails']);
    }

    public function test_pro_writes_its_emails_and_the_website_gets_them(): void
    {
        Mail::fake();
        $user = $this->customer(['plan' => 'pro']);
        $this->actingAs($user)->get(route('emails'))->assertOk()->assertSee('Review request');

        // Review on without a link: refused.
        $this->actingAs($user)->post(route('emails'), ['order_on' => '1', 'order_heading' => 'Thanks {first_name}', 'review_on' => '1', 'review_heading' => 'How are they?', 'review_url' => ''])
            ->assertSessionHasErrors('review_url');

        $this->actingAs($user)->post(route('emails'), [
            'order_on' => '1', 'order_heading' => 'Thanks {first_name}', 'order_message' => 'We make them by hand.', 'order_needs' => 'Your V5C.', 'order_next' => '',
            'review_on' => '1', 'review_heading' => 'How are they?', 'review_message' => 'A review helps.', 'review_url' => 'https://g.page/r/abc/review',
            'test' => 'order',
        ])->assertSessionHasNoErrors();

        Mail::assertSent(CustomerEmailTest::class, fn ($m) => $m->hasTo($user->email) && $m->which === 'order');
        $site = SiteConfig::build($user->shop->fresh())['emails'];
        $this->assertSame('Thanks {first_name}', $site['order']['order_heading']);
        $this->assertSame('', $site['order']['order_next']);
        $this->assertSame(['heading' => 'How are they?', 'message' => 'A review helps.', 'url' => 'https://g.page/r/abc/review', 'days' => 3], $site['review']);

        // Switched off: the website gets nothing for that email.
        $this->actingAs($user)->post(route('emails'), ['order_on' => '0', 'review_on' => '0', 'order_heading' => '', 'review_heading' => '']);
        $site = SiteConfig::build($user->shop->fresh())['emails'];
        $this->assertNull($site['order']);
        $this->assertNull($site['review']);
    }

    public function test_the_test_email_renders(): void
    {
        $user = $this->customer(['plan' => 'complete', 'accent_colour' => '#C8361C']);
        $mail = new CustomerEmailTest($user->shop, 'review', \App\Support\EmailSettings::forShop($user->shop));
        $html = $mail->render();
        $this->assertStringContainsString('How are your new plates, Sam?', $html);
        $this->assertStringContainsString('#C8361C', $html);
    }
}
