<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('market', 10);
            $table->char('currency', 3);
            $table->unsignedBigInteger('price_minor')->default(0);
            $table->string('interval', 20)->default('month');
            $table->unsignedSmallInteger('commission_bps')->default(0); // 2000 = 20%
            $table->boolean('shows_linkedin')->default(false);
            $table->boolean('direct_dealing')->default(false);
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->index(['market', 'is_active', 'sort']);
        });

        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending_payment');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_subscription_id')->nullable()->index();
            $table->string('gateway_customer_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'status']);
            $table->index(['status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('plans');
    }
};
