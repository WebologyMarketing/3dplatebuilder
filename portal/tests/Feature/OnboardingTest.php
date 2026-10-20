<?php

namespace Tests\Feature;

use App\Mail\PortalNotice;
use App\Models\AddonRequest;
use App\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_details_save_and_tidy_up(): void
    {
        $user = $this->customer();
        $this->actingAs($user)->post(route('details'), [
            'legal_name' => 'Your Plates Ltd', 'company_number' => '01234567', 'registered_address' => '1 High St',
            'contact_email' => 'orders@yourplates.test', 'domain' => 'www.YourPlates.co.uk',
            'supplier_name' => 'YOUR PLATES', 'supplier_postcode' => 'ab1   2cd', 'rnps_number' => 'RNPS123',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $shop = $user->shop->fresh();
        $this->assertSame('yourplates.co.uk', $shop->domain);
        $this->assertSame('AB1 2CD', $shop->supplier_postcode);
        $steps = collect($shop->onboardingSteps())->keyBy('key');
        $this->assertTrue($steps['company']['done']);
        $this->assertTrue($steps['contact']['done']);
        $this->assertFalse($steps['logo']['done']);
    }

    public function test_brand_form_saves_only_its_own_fields(): void
    {
        $user = $this->customer(['legal_name' => 'Keep Me Ltd']);
        $this->actingAs($user)->post(route('details'), ['accent_colour' => '#e8452c', 'dark_colour' => '#16191c', 'heading_font' => 'Archivo'])
            ->assertSessionHasNoErrors();
        $shop = $user->shop->fresh();
        $this->assertSame('Keep Me Ltd', $shop->legal_name);
        $this->assertSame('#e8452c', $shop->accent_colour);

        $this->actingAs($user)->post(route('details'), ['accent_colour' => 'red', 'supplier_postcode' => 'nope', 'heading_font' => 'Comic Sans'])
            ->assertSessionHasErrors(['accent_colour', 'supplier_postcode', 'heading_font']);
    }

    public function test_logo_upload_and_safe_serving(): void
    {
        Storage::fake('local');
        $user = $this->customer();
        $svg = UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs($user)->post(route('details.logo'), ['logo' => $svg])->assertRedirect();
        $shop = $user->shop->fresh();
        Storage::disk('local')->assertExists($shop->logo_path);

        $res = $this->actingAs($user)->get(route('details.file', 'logo'))->assertOk();
        $this->assertStringContainsString('sandbox', $res->headers->get('Content-Security-Policy'));

        $this->actingAs($user)->post(route('details.logo'), ['logo' => UploadedFile::fake()->create('logo.exe', 10)])->assertSessionHasErrors('logo');
    }

    public function test_rnps_document_review_round_trip(): void
    {
        Storage::fake('local');
        Mail::fake();
        $user = $this->customer();

        $this->actingAs($user)->post(route('details.document'), ['document' => UploadedFile::fake()->create('rnps.pdf', 200, 'application/pdf')])->assertRedirect();
        $doc = Document::first();
        $this->assertSame('pending', $doc->status);
        Mail::assertSent(PortalNotice::class, fn ($m) => $m->hasTo(config('portal.admin_email')));

        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.documents.review', $doc), ['decision' => 'rejected'])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post(route('admin.documents.review', $doc), ['decision' => 'rejected', 'note' => 'Expired'])->assertRedirect();
        $this->assertSame('rejected', $doc->fresh()->status);
        Mail::assertSent(PortalNotice::class, fn ($m) => $m->hasTo($user->email) && in_array('Reason: Expired', $m->lines, true));

        $this->actingAs($admin)->post(route('admin.documents.review', $doc), ['decision' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $user->shop->fresh()->rnpsStatus());
    }

    public function test_extra_work_requests(): void
    {
        Mail::fake();
        $user = $this->customer();

        $this->actingAs($user)->post(route('products.addons'), ['addons' => ['addon_plate_logo', 'addon_migration']])->assertRedirect();
        $this->actingAs($user)->post(route('products.addons'), ['addons' => ['addon_plate_logo']]); // asked twice
        $this->assertSame(2, AddonRequest::count());

        $req = AddonRequest::where('addon', 'addon_plate_logo')->first();
        $this->actingAs($this->admin())->post(route('admin.addons.invoice', $req))->assertRedirect();
        $this->assertSame('invoiced', $req->fresh()->status);
        $this->assertSame([[$user->shop_id, 'My logo on the plate', 12500]], $this->billing->items);
    }
}
