<?php

namespace Tests\Feature;

use App\Mail\PortalNotice;
use App\Models\Release;
use App\Models\Shop;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAndSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_see_the_admin_and_admins_skip_the_account(): void
    {
        $this->actingAs($this->customer())->get('/admin')->assertNotFound();
        $this->actingAs($this->admin())->get('/account')->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_sign_in_sends_each_kind_of_user_to_their_home(): void
    {
        $c = $this->customer();
        $this->post('/login', ['email' => $c->email, 'password' => 'password'])->assertRedirect(route('dashboard'));
        $this->post('/logout');
        $a = $this->admin();
        $this->post('/login', ['email' => $a->email, 'password' => 'password'])->assertRedirect(route('admin.dashboard'));
        $this->post('/logout');
        $this->post('/login', ['email' => $a->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
    }

    public function test_dashboard_numbers(): void
    {
        $this->customer(['name' => 'A', 'plan' => 'starter', 'stage' => 'live']);
        $this->customer(['name' => 'B', 'plan' => 'pro', 'stage' => 'live']);
        $this->customer(['name' => 'C', 'plan' => 'complete', 'stage' => 'in_build']);
        $this->customer(['name' => 'D', 'plan' => 'pro'], paid: false);
        $this->customer(['name' => 'E', 'plan' => 'pro', 'stage' => 'cancelled', 'cancelled_at' => now()->subMonth()]);

        $this->actingAs($this->admin())->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('£697')          // 99 + 199 + 399
            ->assertSee('£8,364 a year')
            ->assertSee('Churn 25%');    // 1 cancelled of 4
        $this->actingAs($this->admin())->get(route('admin.shops', ['show' => 'setup']))->assertSee('C')->assertSee('D')->assertDontSee('>A<', false);
    }

    public function test_admin_moves_a_shop_to_review_and_they_are_emailed(): void
    {
        Mail::fake();
        $user = $this->customer();
        $this->actingAs($this->admin())->post(route('admin.shops.update', $user->shop), [
            'stage' => 'review', 'preview_url' => 'https://preview.example.test', 'admin_notes' => 'Wants blue',
        ])->assertRedirect();
        $this->assertSame('review', $user->shop->fresh()->stage);
        Mail::assertSent(PortalNotice::class, fn ($m) => $m->hasTo($user->email) && $m->url === 'https://preview.example.test');

        $this->actingAs($this->admin())->get(route('admin.shops.export', $user->shop))->assertOk()->assertJsonPath('shop.name', 'Your Plates Ltd');
    }

    public function test_ticket_round_trip_and_privacy(): void
    {
        Mail::fake();
        $user = $this->customer();
        $other = $this->customer(['name' => 'Other Ltd']);

        $this->actingAs($user)->post(route('support'), ['subject' => 'Change 4D price', 'body' => 'Make it £40 please'])->assertRedirect();
        $ticket = Ticket::first();
        $this->assertSame('new', $ticket->status);

        $this->actingAs($other)->get(route('support.show', $ticket))->assertNotFound();

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), ['body' => 'Done!'])->assertRedirect();
        $this->assertSame('waiting_on_them', $ticket->fresh()->status);
        Mail::assertSent(PortalNotice::class, fn ($m) => $m->hasTo($user->email));

        $this->actingAs($user)->get(route('support.show', $ticket))->assertSee('Done!')->assertSee('Waiting on you');
        $this->actingAs($user)->post(route('support.reply', $ticket), ['body' => 'Thanks'])->assertRedirect();
        $this->assertSame('waiting_on_us', $ticket->fresh()->status);

        $this->actingAs($admin)->post(route('admin.tickets.reply', $ticket), ['close' => '1'])->assertRedirect(route('admin.tickets'));
        $this->assertSame('closed', $ticket->fresh()->status);
    }

    public function test_plugin_updates_need_a_live_licence(): void
    {
        Storage::fake('local');
        $user = $this->customer(['licence_key' => 'AAAAA-BBBBB-CCCCC-DDDDD', 'subscription_status' => 'active']);

        $this->actingAs($this->admin())->post(route('admin.releases'), [
            'version' => '1.10.4', 'zip' => UploadedFile::fake()->create('p.zip', 100, 'application/zip'), 'notes' => 'Fixes',
        ])->assertSessionHasNoErrors();
        Release::create(['version' => '1.9.0', 'path' => 'releases/old.zip']);

        $this->getJson('/updates/check?licence=nope')->assertStatus(403);
        $json = $this->getJson('/updates/check?licence=aaaaa-bbbbb-ccccc-ddddd&version=1.10.3')->assertOk()->assertJsonPath('version', '1.10.4')->json();

        $this->get($json['download_url'])->assertOk()->assertDownload('3d-plate-builder-1.10.4.zip');
        $this->get(route('updates.download', Release::where('version', '1.10.4')->first()))->assertForbidden(); // unsigned

        $user->shop->update(['stage' => 'cancelled']);
        $this->getJson('/updates/check?licence=AAAAA-BBBBB-CCCCC-DDDDD')->assertStatus(403);
    }

    public function test_every_page_renders(): void
    {
        $user = $this->customer(['products' => ['styles' => ['printed' => '30.00'], 'sizes' => ['standard'], 'extras' => []]]);
        foreach (['dashboard', 'details', 'products', 'billing', 'support'] as $r) {
            $this->actingAs($user)->get(route($r))->assertOk();
        }
        $admin = $this->admin();
        foreach (['admin.dashboard', 'admin.shops', 'admin.documents', 'admin.tickets', 'admin.payments', 'admin.releases'] as $r) {
            $this->actingAs($admin)->get(route($r))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.shops.show', Shop::first()))->assertOk();
        $this->post('/logout');
        foreach (['/login', '/signup', '/forgot-password'] as $u) {
            $this->get($u)->assertOk();
        }
    }
}
