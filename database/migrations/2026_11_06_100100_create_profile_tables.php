<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('practitioner_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title', 20)->nullable(); // Dr., Prof., Ms. …
            $table->string('gender', 20)->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('timezone', 64)->default('Asia/Karachi');
            $table->string('market', 10)->default('pk');
            $table->foreignId('primary_specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->string('headline', 160)->nullable();
            $table->text('qualifications')->nullable();
            $table->string('license_number', 80)->nullable();
            $table->string('licensing_body', 120)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->unsignedBigInteger('fee_minor')->nullable();
            $table->char('fee_currency', 3)->default('PKR');
            $table->unsignedSmallInteger('session_minutes')->default(30);
            $table->text('bio')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('intro_video_url')->nullable();
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('other_languages')->nullable();
            $table->string('headshot_path')->nullable();

            $table->string('verification_status', 20)->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Denormalised: approved + active user + active membership. Only VisibilityService writes it.
            $table->boolean('is_public')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_public', 'primary_specialty_id']);
            $table->index(['is_public', 'country_id', 'city_id']);
            $table->index(['is_public', 'gender']);
            $table->index(['is_public', 'fee_currency', 'fee_minor']);
            $table->index('verification_status');
            $table->fullText(['headline', 'bio', 'qualifications']);
        });

        Schema::create('practitioner_specialty', function (Blueprint $table) {
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->primary(['practitioner_profile_id', 'specialty_id']);
            $table->index('specialty_id');
        });

        Schema::create('condition_practitioner_profile', function (Blueprint $table) {
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained()->cascadeOnDelete();
            $table->primary(['practitioner_profile_id', 'condition_id']);
            $table->index('condition_id');
        });

        Schema::create('language_practitioner_profile', function (Blueprint $table) {
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->primary(['practitioner_profile_id', 'language_id']);
            $table->index('language_id');
        });

        Schema::create('practitioner_profile_tag', function (Blueprint $table) {
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['practitioner_profile_id', 'tag_id']);
            $table->index('tag_id');
        });

        Schema::create('practitioner_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('path'); // private disk only
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedInteger('size_bytes');
            $table->char('sha256', 64);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('note', 500)->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practitioner_documents');
        Schema::dropIfExists('practitioner_profile_tag');
        Schema::dropIfExists('language_practitioner_profile');
        Schema::dropIfExists('condition_practitioner_profile');
        Schema::dropIfExists('practitioner_specialty');
        Schema::dropIfExists('practitioner_profiles');
        Schema::dropIfExists('patient_profiles');
    }
};
