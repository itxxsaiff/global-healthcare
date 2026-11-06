<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

/**
 * Converts uploads to compressed WebP on the public disk (PageSpeed requirement).
 * EXIF data (GPS location etc.) is stripped for privacy.
 */
class ImageService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = ImageManager::usingDriver(GdDriver::class);
    }

    /** Square practitioner headshot, 600×600 WebP. */
    public function storeHeadshot(UploadedFile $file, ?string $replacePath = null): string
    {
        $image = $this->manager->decodePath($file->getRealPath())->orient()->cover(600, 600);
        $encoded = $image->encode(new WebpEncoder(quality: 82, strip: true));

        return $this->put('headshots', $encoded->toString(), $replacePath);
    }

    /**
     * Article featured image: 1200×630 WebP, quality stepped down until it fits
     * the size budget (client document: 100–200 KB).
     */
    public function storeArticleImage(UploadedFile $file, int $targetKb = 200, ?string $replacePath = null): string
    {
        $image = $this->manager->decodePath($file->getRealPath())->orient()->cover(1200, 630);

        $quality = 82;
        do {
            $encoded = $image->encode(new WebpEncoder(quality: $quality, strip: true));
            $quality -= 8;
        } while ($encoded->size() > $targetKb * 1024 && $quality >= 30);

        return $this->put('articles', $encoded->toString(), $replacePath);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }

    private function put(string $dir, string $binary, ?string $replacePath): string
    {
        $path = $dir.'/'.now()->format('Y/m').'/'.Str::uuid().'.webp';
        Storage::disk('public')->put($path, $binary, ['visibility' => 'public']);
        $this->delete($replacePath);

        return $path;
    }
}
