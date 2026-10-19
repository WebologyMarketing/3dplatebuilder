<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Made-up shops for trying the portal locally or on staging: php artisan db:seed --class=DemoSeeder
 * Refuses to run in production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder is for local and staging only.');
        }

        $admin = User::firstOrNew(['email' => 'admin@demo.test']);
        $admin->fill(['name' => 'Harsh Demo', 'password' => 'demo-password-123']);
        $admin->is_admin = true;
        $admin->save();

        $shops = [
            ['Apex Plates Direct', 'complete', 'live', 7, 'apexplates.co.uk'],
            ['Midland Show Plates', 'pro', 'live', 5, 'midlandshowplates.co.uk'],
            ['North Road Plates', 'starter', 'live', 9, 'northroadplates.co.uk'],
            ['Kestrel Number Plates', 'starter', 'live', 6, 'kestrelplates.co.uk'],
            ['Plate Lab Leeds', 'starter', 'live', 4, 'platelableeds.co.uk'],
            ['Ridgeway Reg Plates', 'pro', 'review', 1, null],
            ['Coastline Plates', 'starter', 'in_build', 0, null],
            ['Your Plates Ltd', 'pro', 'onboarding', 0, null],
            ['Harbour Plates', 'starter', 'not_paid', null, null],
        ];

        foreach ($shops as $i => [$name, $plan, $stage, $monthsAgo, $domain]) {
            $paid = $monthsAgo !== null;
            $shop = Shop::create([
                'name' => $name, 'plan' => $plan, 'stage' => $stage,
                'setup_paid_at' => $paid ? now()->subMonths($monthsAgo)->subDays(3) : null,
                'live_at' => $stage === 'live' ? now()->subMonths($monthsAgo)->addDays(10) : null,
                'stripe_subscription_id' => $stage === 'live' ? 'sub_demo'.$i : null,
                'subscription_status' => $paid ? 'active' : null,
                'current_period_end' => $paid ? now()->addDays(3 + $i * 2) : null,
                'stripe_customer_id' => $paid ? 'cus_demo'.$i : null,
                'licence_key' => $paid ? Shop::newLicenceKey() : null,
                'domain' => $domain,
                'live_url' => $domain ? 'https://'.$domain : null,
                'preview_url' => $stage === 'review' ? 'https://preview.example.com/ridgeway' : null,
                'legal_name' => $stage === 'onboarding' ? null : $name,
                'company_number' => $stage === 'onboarding' ? null : '1234567'.$i,
                'registered_address' => $stage === 'onboarding' ? null : "Unit $i, Example Park\nTown\nAB1 2CD",
                'contact_email' => 'orders@'.($domain ?: 'example.com'),
                'created_at' => $paid ? now()->subMonths($monthsAgo)->subDays(5) : now()->subDays(2),
            ]);
            if ($name === 'Your Plates Ltd') {
                $shop->update(['legal_name' => 'Your Plates Ltd', 'company_number' => '01234567', 'registered_address' => "1 High Street\nAnytown\nAB1 2CD",
                    'products' => ['styles' => ['printed' => '24.99', 'gel-3d' => '39.99'], 'sizes' => ['standard'], 'extras' => []]]);
            }
            User::create(['name' => 'Owner '.($i + 1), 'email' => 'owner'.($i + 1).'@demo.test', 'password' => 'demo-password-123', 'shop_id' => $shop->id]);

            if ($paid) {
                Payment::create(['shop_id' => $shop->id, 'stripe_invoice_id' => 'in_demo_'.$i, 'description' => $shop->planName().' plan and set-up fee',
                    'amount_pence' => $shop->planConfig()['monthly'] + $shop->planConfig()['setup'], 'amount_paid_pence' => $shop->planConfig()['monthly'] + $shop->planConfig()['setup'],
                    'status' => 'paid', 'paid_at' => $shop->setup_paid_at, 'created_at' => $shop->setup_paid_at]);
            }
        }

        $kestrel = Shop::where('name', 'Kestrel Number Plates')->first();
        Payment::create(['shop_id' => $kestrel->id, 'stripe_invoice_id' => 'in_demo_late', 'description' => 'Starter plan, October',
            'amount_pence' => 9900, 'status' => 'open', 'attempt_count' => 2, 'due_at' => now()->subDays(9), 'created_at' => now()->subDays(9)]);

        foreach ([['Midland Show Plates', 'Change 4D Gel price', 'new'], ['Plate Lab Leeds', 'Logo looks blurry on phone', 'waiting_on_us'], ['North Road Plates', 'Add a collection address', 'waiting_on_them']] as $k => [$shop, $subject, $status]) {
            $s = Shop::where('name', $shop)->first();
            $t = Ticket::create(['shop_id' => $s->id, 'subject' => $subject, 'status' => $status, 'last_reply_at' => now()->subHours(2 + $k * 20)]);
            $t->messages()->create(['user_id' => $s->users()->first()->id, 'body' => 'Hi, could you help with this please?']);
        }

        foreach (['Your Plates Ltd', 'Coastline Plates'] as $name) {
            $s = Shop::where('name', $name)->first();
            Document::create(['shop_id' => $s->id, 'type' => 'rnps', 'path' => 'demo/none.pdf', 'original_name' => 'rnps-certificate.pdf', 'status' => 'pending']);
        }
    }
}
