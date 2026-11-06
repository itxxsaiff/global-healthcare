<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->char('iso2', 2)->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->char('currency', 3);
            $table->string('default_timezone', 64);
            $table->boolean('is_target')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['country_id', 'slug']);
            $table->index('slug');
        });

        Schema::create('specialties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('specialties')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('practitioner_label')->nullable(); // singular, e.g. "Dermatologist"
            $table->string('summary', 500)->nullable();
            $table->string('icon', 40)->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'is_active', 'sort']);
        });

        Schema::create('conditions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('summary', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->fullText('name');
        });

        Schema::create('condition_specialty', function (Blueprint $table) {
            $table->foreignId('condition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->cascadeOnDelete();
            $table->primary(['condition_id', 'specialty_id']);
            $table->index('specialty_id');
        });

        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->boolean('is_preset')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('suggested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('condition_specialty');
        Schema::dropIfExists('conditions');
        Schema::dropIfExists('specialties');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
    }
};
