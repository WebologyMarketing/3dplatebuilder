<?php

namespace Tests\Feature;

use App\Models\ShopProduct;
use App\Services\Publisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SelfServeProductsTest extends TestCase
{
    use RefreshDatabase;

    private function builderInput(array $over = []): array
    {
        return array_replace_recursive([
            'styles' => [
                'printed' => ['on' => '0', 'pair' => '24.5', 'single' => '15'], // printed can't be switched off
                'gel-3d' => ['on' => '1', 'pair' => '39.99', 'single' => ''],
                'retro' => ['on' => '0', 'pair' => '99', 'single' => '50'],
            ],
            'sizes' => ['short' => ['on' => '1', 'price' => '12'], 'motorbike' => ['on' => '0', 'price' => '25']],
            'badges' => ['uk-4d' => ['on' => '0', 'price' => '15']],
            'extras' => ['xl_pads' => ['on' => '0']],
            'fitting' => '35',
            'custom' => [
                ['name' => 'Gift box', 'price' => '3', 'on' => '1'],
                ['name' => '', 'price' => '9', 'on' => '1'], // empty rows are ignored
            ],
        ], $over);
    }

    public function test_saving_the_builder_and_what_the_website_gets(): void
    {
        $user = $this->customer(['plan' => 'starter']);
        $this->actingAs($user)->get(route('builder'))->assertOk()->assertSee('Your builder and prices')->assertSee('the builder is your product');

        $this->actingAs($user)->post(route('builder'), $this->builderInput())->assertSessionHasNoErrors()->assertRedirect(route('builder'));

        $shop = $user->shop->fresh();
        $this->assertTrue($shop->builder['styles']['printed']['on']);
        $this->assertSame('24.50', $shop->builder['styles']['printed']['pair']);
        $this->assertSame('29.99', $shop->builder['styles']['gel-3d']['single']); // blank: back to the default
        $this->assertFalse($shop->builder['styles']['retro']['on']);
        $this->assertCount(1, $shop->builder['custom']);
        $giftId = $shop->builder['custom'][0]['id'];
        $this->assertMatchesRegularExpression('/^c_[a-z0-9]{6}$/', $giftId);

        $site = \App\Support\BuilderSettings::forSite($shop);
        $this->assertNotContains('retro', $site['offer']['styles']);
        $this->assertContains('printed', $site['offer']['styles']);
        $this->assertNotContains('motorbike', $site['offer']['sizes']);
        $this->assertSame(['none', 'uk', 'uk-gel', 'uk-gel-blue', 'ev', 'ev-gel', 'ev-uk-gel'], $site['offer']['badges']);
        $this->assertSame(12.0, $site['pricing']['size']['short']);
        $this->assertSame(25.0, $site['pricing']['flat']['motorbike']);
        $this->assertSame(35.0, $site['pricing']['services']['fitting']);
        $this->assertSame(3.0, $site['pricing']['accessories'][$giftId]);
        $this->assertSame('Gift box', $site['accessories'][$giftId]);
        $this->assertArrayNotHasKey('xl_pads', $site['accessories']);

        // Renaming keeps the id, so past orders still match.
        $this->actingAs($user)->post(route('builder'), $this->builderInput(['custom' => [['id' => $giftId, 'name' => 'Luxury gift box', 'price' => '4', 'on' => '1'], ['name' => '']]]));
        $this->assertSame($giftId, $user->shop->fresh()->builder['custom'][0]['id']);

        $this->actingAs($user)->post(route('builder'), ['styles' => ['gel-3d' => ['pair' => 'cheap']]])->assertSessionHasErrors('styles.gel-3d.pair');
    }

    public function test_starter_has_no_products_page(): void
    {
        $user = $this->customer(['plan' => 'starter']);
        $this->actingAs($user)->get(route('products'))->assertRedirect(route('builder'));
        $this->actingAs($user)->post(route('products.store'), ['type' => 'simple', 'name' => 'X', 'price' => '1'])->assertRedirect(route('builder'));
        $this->assertSame(0, ShopProduct::count());
        $this->actingAs($user)->get(route('dashboard'))->assertDontSee('>Products<', false);
    }

    public function test_adding_editing_and_ordering_products(): void
    {
        Storage::fake('local');
        $user = $this->customer(['plan' => 'pro']);

        $this->actingAs($user)->post(route('products.store'), [
            'type' => 'plate', 'name' => '4D 3mm plates', 'style' => 'acrylic-4d-3', 'size' => '', 'badge' => 'uk', 'active' => '1',
            'description' => 'Laser-cut.', 'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
        ])->assertSessionHasNoErrors()->assertRedirect(route('products'));
        $this->actingAs($user)->post(route('products.store'), ['type' => 'simple', 'name' => 'Keyring', 'price' => '7.5', 'active' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('products.store'), ['type' => 'simple', 'name' => 'No price'])->assertSessionHasErrors('price');

        [$plate, $ring] = $user->shop->shopProducts()->get()->all();
        $this->assertSame('acrylic-4d-3', $plate->style);
        $this->assertNull($plate->size);
        $this->assertCount(2, $plate->images);
        $this->assertSame(750, $ring->price_pence);

        $this->actingAs($user)->get(route('products'))->assertOk()->assertSeeInOrder(['4D 3mm plates', 'From £49.99', 'Keyring', '£7.50']);
        $this->actingAs($user)->get(route('products.image', [$plate, 0]))->assertOk();

        // Remove the first image; move the keyring up.
        $this->actingAs($user)->post(route('products.update', $plate), ['type' => 'plate', 'name' => '4D plates', 'style' => 'acrylic-4d-3', 'active' => '1', 'remove' => [0]])->assertSessionHasNoErrors();
        $this->assertCount(1, $plate->fresh()->images);
        $this->actingAs($user)->post(route('products.move', $ring), ['dir' => 'up']);
        $this->assertSame(['Keyring', '4D plates'], $user->shop->shopProducts()->pluck('name')->all());

        // Someone else's product is out of reach.
        $other = $this->customer(['plan' => 'pro', 'name' => 'Other']);
        $this->actingAs($other)->get(route('products.edit', $plate))->assertNotFound();
        $this->actingAs($other)->post(route('products.destroy', $plate))->assertNotFound();

        $this->actingAs($user)->post(route('products.destroy', $ring))->assertRedirect(route('products'));
        $this->assertSame(1, ShopProduct::count());
    }

    public function test_publish_then_the_website_fetches_it(): void
    {
        Storage::fake('local');
        Http::fake(['https://plates.example/*' => Http::response(['ok' => true])]);
        $user = $this->customer(['plan' => 'pro', 'licence_key' => 'AAAAA-BBBBB-CCCCC-DDDDD', 'preview_url' => 'https://plates.example',
            'supplier_name' => 'Your Plates', 'supplier_postcode' => 'tw3 1aa', 'accent_colour' => '#F7D333']);
        $shop = $user->shop;
        $shop->shopProducts()->create(['type' => 'plate', 'name' => 'Gel plates', 'style' => 'gel-3d', 'images' => [UploadedFile::fake()->image('g.jpg')->store("shops/{$shop->id}/products", 'local')]]);
        $shop->shopProducts()->create(['type' => 'simple', 'name' => 'Hidden one', 'price_pence' => 100, 'active' => false]);

        $api = fn (?string $key = 'AAAAA-BBBBB-CCCCC-DDDDD') => $this->withHeaders(['X-Licence-Key' => (string) $key])->getJson('/api/v1/site');

        $api()->assertOk()->assertExactJson(['version' => 0]); // nothing published yet
        $api('WRONG')->assertStatus(403);
        $this->assertTrue($shop->fresh()->hasUnpublishedChanges());

        $this->actingAs($user)->post(route('publish'))->assertSessionHas('status', 'Published. Your website is updating now; give it a minute.');
        Http::assertSent(function ($req) use ($shop) {
            return $req->url() === 'https://plates.example/wp-json/pbb/v1/sync'
                && $req->header('X-PBB-Signature')[0] === Publisher::signature('AAAAA-BBBBB-CCCCC-DDDDD', $req->header('X-PBB-Time')[0], '1');
        });
        $this->assertFalse($shop->fresh()->hasUnpublishedChanges());

        // Changes after publishing don't reach the website until the next publish.
        $this->actingAs($user)->post(route('builder'), ['styles' => ['gel-3d' => ['on' => '1', 'pair' => '45']]]);
        $this->assertTrue($shop->fresh()->hasUnpublishedChanges());

        $json = $api()->assertOk()
            ->assertJsonPath('version', 1)
            ->assertJsonPath('shop.supplier', 'Your Plates')
            ->assertJsonPath('shop.postcode', 'TW3 1AA')
            ->assertJsonPath('shop.accent_ink', '#0D0E10')
            ->assertJsonPath('builder.pricing.style_pair.gel-3d', 49.99)
            ->assertJsonPath('products.0.name', 'Gel plates')
            ->assertJsonPath('products.0.preset.style', 'gel-3d')
            ->assertJsonPath('products.0.price', 49.99)
            ->assertJsonCount(1, 'products')
            ->json();

        // Image links are signed and only work for this shop's files.
        $img = $json['products'][0]['images'][0];
        $this->assertStringEndsWith('.jpg', $img['key']);
        $this->get($img['url'])->assertOk();
        $this->get(preg_replace('/signature=\w+/', 'signature=nope', $img['url']))->assertForbidden();

        $this->withHeaders(['X-Licence-Key' => 'AAAAA-BBBBB-CCCCC-DDDDD'])->postJson('/api/v1/site/applied', ['version' => 1])->assertOk();
        $this->assertSame(1, $shop->fresh()->site_version);
        $this->assertNotNull($shop->fresh()->site_checked_at);
    }

    public function test_publish_without_a_website_yet(): void
    {
        Http::fake();
        $user = $this->customer(['plan' => 'starter']);
        $this->actingAs($user)->post(route('publish'))->assertSessionHas('status', fn ($s) => str_contains($s, 'when we build your shop'));
        Http::assertNothingSent();
        $this->assertSame(1, $user->shop->fresh()->published_version);
        $steps = collect($user->shop->fresh()->onboardingSteps())->keyBy('key');
        $this->assertTrue($steps['products']['done']);
    }

    public function test_cancelled_shops_get_nothing(): void
    {
        $user = $this->customer(['licence_key' => 'AAAAA-BBBBB-CCCCC-EEEEE', 'stage' => 'cancelled']);
        $this->withHeaders(['X-Licence-Key' => 'AAAAA-BBBBB-CCCCC-EEEEE'])->getJson('/api/v1/site')->assertStatus(403);
    }
}
