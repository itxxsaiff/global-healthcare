<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Optional intro video: a YouTube/Vimeo link (intro_video_url, existing) OR an uploaded
 * file on the public disk. Only one is kept at a time.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('practitioner_profiles', function (Blueprint $table) {
            $table->string('intro_video_path')->nullable()->after('intro_video_url');
            $table->unsignedSmallInteger('intro_video_seconds')->nullable()->after('intro_video_path');
        });
    }

    public function down(): void
    {
        Schema::table('practitioner_profiles', function (Blueprint $table) {
            $table->dropColumn(['intro_video_path', 'intro_video_seconds']);
        });
    }
};
