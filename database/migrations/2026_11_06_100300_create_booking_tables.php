<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday'); // 0 = Sunday … 6 = Saturday, practitioner local time
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'weekday']);
        });

        Schema::create('availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_unavailable')->default(true);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'date']);
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('patient_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('practitioner_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at'); // UTC
            $table->dateTime('ends_at');   // UTC
            $table->string('practitioner_timezone', 64);
            $table->string('patient_timezone', 64);
            $table->string('status', 30);
            $table->string('flow', 30);
            $table->unsignedBigInteger('fee_minor');
            $table->char('currency', 3);
            $table->unsignedSmallInteger('commission_bps')->default(0);
            $table->unsignedBigInteger('commission_minor')->default(0);
            $table->unsignedBigInteger('practitioner_earning_minor')->default(0);
            $table->text('patient_note')->nullable();     // encrypted cast
            $table->text('meeting_details')->nullable();  // encrypted cast
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('respond_by')->nullable();
            $table->timestamp('payment_due_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cancellation_reason', 500)->nullable();
            $table->boolean('refund_due')->default(false);
            $table->timestamps();

            $table->index(['patient_id', 'status']);
            $table->index(['practitioner_profile_id', 'status', 'starts_at']);
            $table->index(['status', 'respond_by']);
            $table->index(['status', 'payment_due_at']);
            $table->index(['status', 'ends_at']);
        });

        // Database-level double-booking guard: the slot start is "occupied" only
        // while the booking is in a holding status; MySQL allows many NULLs in a UNIQUE index.
        DB::statement("ALTER TABLE bookings ADD COLUMN active_slot DATETIME
            GENERATED ALWAYS AS (IF(status IN ('requested','awaiting_payment','confirmed'), starts_at, NULL)) STORED");
        DB::statement('CREATE UNIQUE INDEX bookings_practitioner_active_slot_unique ON bookings (practitioner_profile_id, active_slot)');

        Schema::create('booking_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_status_history');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('availability_exceptions');
        Schema::dropIfExists('availability_rules');
    }
};
