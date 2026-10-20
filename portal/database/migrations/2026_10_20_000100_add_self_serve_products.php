<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plate makers run their own builder prices and products from the portal, then publish them to
 * their website (the plugin pulls the published copy with its licence key).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->json('builder')->nullable();                 // see App\Support\BuilderSettings
            $table->json('published_config')->nullable();        // what the website gets, frozen at Publish
            $table->unsignedInteger('published_version')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('site_version')->nullable(); // the version the website last picked up
            $table->timestamp('site_checked_at')->nullable();
        });

        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('plate');     // plate (opens the builder) | simple (fixed price)
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('style')->nullable();          // plate products: the style the builder opens on
            $table->string('size')->nullable();
            $table->string('badge')->nullable();
            $table->unsignedInteger('price_pence')->nullable(); // simple products
            $table->json('images')->nullable();           // storage paths, first is the main image
            $table->boolean('active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Carry over pair prices and ticks from the first version of the products page.
        foreach (DB::table('shops')->whereNotNull('products')->get(['id', 'products']) as $row) {
            $old = json_decode($row->products, true) ?: [];
            $builder = ['styles' => []];
            foreach (array_keys(config('portal.catalogue.styles')) as $id) {
                $builder['styles'][$id] = ['on' => array_key_exists($id, $old['styles'] ?? [])];
                if (! empty($old['styles'][$id])) {
                    $builder['styles'][$id]['pair'] = $old['styles'][$id];
                }
            }
            if (! empty($old['sizes'])) {
                foreach (array_keys(config('portal.catalogue.sizes')) as $id) {
                    $builder['sizes'][$id] = ['on' => in_array($id, $old['sizes'], true)];
                }
            }
            DB::table('shops')->where('id', $row->id)->update(['builder' => json_encode($builder)]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['builder', 'published_config', 'published_version', 'published_at', 'site_version', 'site_checked_at']);
        });
    }
};
