<?php

namespace App\Services\Media;

use getID3;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Practitioner intro videos uploaded to the public disk. Files are served straight
 * by the web server (range requests work, nothing passes through PHP) and are only
 * requested when a visitor presses play.
 */
class VideoService
{
    public const DIR = 'intro-videos';

    /** Length in seconds, read from the file header (pure PHP, no ffmpeg needed). */
    public function duration(UploadedFile $file): ?float
    {
        // Servers updated by file upload (no `composer install`) may not have it in the
        // autoloader yet; getID3 is self-contained, so load it directly.
        if (! class_exists(getID3::class)) {
            $entry = base_path('vendor/james-heinrich/getid3/getid3/getid3.php');
            if (is_file($entry)) {
                require_once $entry;
            }
        }

        try {
            $info = (new getID3)->analyze($file->getRealPath());
        } catch (\Throwable) {
            return null;
        }
        $seconds = $info['playtime_seconds'] ?? null;

        return is_numeric($seconds) && $seconds > 0 ? (float) $seconds : null;
    }

    /** @return array{path: string, seconds: int|null} */
    public function storeIntro(UploadedFile $file, ?string $replacePath = null): array
    {
        $seconds = $this->duration($file);
        $ext = match ($file->getMimeType()) {
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => 'mp4',
        };
        $path = $file->storeAs(self::DIR, Str::random(32).'.'.$ext, 'public');
        $this->delete($replacePath);

        return ['path' => $path, 'seconds' => $seconds ? (int) round($seconds) : null];
    }

    public function delete(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR.'/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
