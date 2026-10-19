<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('plan');                       // starter | pro | complete | outright
            $table->string('stage')->default('not_paid'); // see App\Models\Shop::STAGES
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->index();
            $table->string('subscription_status')->nullable(); // Stripe's status: active, past_due, canceled…
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('setup_paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Company
            $table->string('legal_name')->nullable();
            $table->string('trading_name')->nullable();
            $table->string('company_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->text('registered_address')->nullable();

            // Contact and supplier
            $table->string('contact_email')->nullable();
            $table->string('domain')->nullable();
            $table->string('supplier_name')->nullable();
            $table->string('supplier_postcode')->nullable();
            $table->string('rnps_number')->nullable();
            $table->text('collection_address')->nullable();

            // Brand
            $table->string('logo_path')->nullable();
            $table->string('logo_white_path')->nullable();
            $table->string('accent_colour', 7)->nullable();
            $table->string('dark_colour', 7)->nullable();
            $table->string('heading_font')->nullable();
            $table->json('font_files')->nullable();

            // Products: {"styles": {"printed": "34.99", …}, "sizes": [...], "extras": [...]}
            $table->json('products')->nullable();

            // Websites
            $table->string('preview_url')->nullable();
            $table->string('live_url')->nullable();

            $table->string('licence_key')->nullable()->unique();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false);
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('type')->default('rnps');
            $table->string('path');
            $table->string('original_name');
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('status')->default('new'); // new | waiting_on_us | waiting_on_them | closed
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('from_admin')->default(false);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('addon_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('addon');
            $table->unsignedInteger('price_pence');
            $table->string('status')->default('requested'); // requested | invoiced | done | cancelled
            $table->string('stripe_invoice_item_id')->nullable();
            $table->timestamps();
        });

        // A copy of each Stripe invoice, kept up to date by the webhook, for the dashboard.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_invoice_id')->unique();
            $table->string('number')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('amount_pence');
            $table->unsignedInteger('amount_paid_pence')->default(0);
            $table->string('status'); // draft | open | paid | uncollectible | void
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('hosted_url', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('releases', function (Blueprint $table) {
            $table->id();
            $table->string('version')->unique();
            $table->string('path');
            $table->text('notes')->nullable();
            $table->string('requires_wp')->nullable();
            $table->string('requires_php')->nullable();
            $table->timestamps();
        });

        Schema::create('stripe_events', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_events');
        Schema::dropIfExists('releases');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('addon_requests');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('documents');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('shop_id');
            $table->dropColumn('is_admin');
        });
        Schema::dropIfExists('shops');
    }
};
