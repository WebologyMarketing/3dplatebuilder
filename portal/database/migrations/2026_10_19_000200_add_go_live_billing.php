<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Sign-up takes the set-up fee and the first month; the monthly subscription starts when the shop goes live.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->string('stripe_payment_method_id')->nullable()->after('stripe_subscription_id');
            $table->timestamp('live_at')->nullable()->after('setup_paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['stripe_payment_method_id', 'live_at']);
        });
    }
};
