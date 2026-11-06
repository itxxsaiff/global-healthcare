<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->string('status', 20)->default('pending');
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable();
            $table->timestamps();

            $table->index(['practitioner_profile_id', 'status']);
        });
        DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_range CHECK (rating BETWEEN 1 AND 5)');

        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete(); // never shown publicly
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 180);
            $table->string('slug')->unique();
            $table->text('body');
            $table->string('asker_gender', 20)->nullable();
            $table->unsignedTinyInteger('asker_age')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('answers_count')->default(0);
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['specialty_id', 'status']);
            $table->fullText(['title', 'body']);
        });

        Schema::create('condition_question', function (Blueprint $table) {
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_id')->constrained()->cascadeOnDelete();
            $table->primary(['question_id', 'condition_id']);
            $table->index('condition_id');
        });

        Schema::create('answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('practitioner_profile_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('status', 20)->default('approved');
            $table->timestamps();

            $table->unique(['question_id', 'practitioner_profile_id']);
            $table->index(['question_id', 'status']);
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('practitioner_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20)->default('standard');
            $table->string('title', 180);
            $table->string('slug')->unique();
            $table->string('excerpt', 300)->nullable();
            $table->longText('body');
            $table->unsignedInteger('word_count')->default(0);
            $table->string('featured_image_path')->nullable();
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 170)->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'published_at']);
            $table->index(['practitioner_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::dropIfExists('answers');
        Schema::dropIfExists('condition_question');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('reviews');
    }
};
