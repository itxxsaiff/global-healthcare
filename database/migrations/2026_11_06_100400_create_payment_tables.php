<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique(); // our order id sent to the gateway
            $table->morphs('payable');      // booking or membership
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('gateway', 30);
            $table->string('gateway_reference')->nullable();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->json('meta')->nullable(); // sanitised gateway data, never card data
            $table->timestamps();

            $table->unique(['gateway', 'gateway_reference']);
            $table->index(['status', 'expires_at']);
        });

        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 30);
            $table->string('event_id');
            $table->string('type', 80)->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->json('payload');
            $table->boolean('signature_valid')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'event_id']); // webhook replay = no-op
        });

        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('method', 40);
            $table->string('reference')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payout_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('type', 30);
            $table->bigInteger('amount_minor'); // signed
            $table->char('currency', 3);
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['booking_id', 'type']); // one commission row per booking, ever
            $table->index(['practitioner_profile_id', 'currency']);
            $table->index(['type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('payment_events');
        Schema::dropIfExists('payments');
    }
};
