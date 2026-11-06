<?php

namespace App\Services\Media;

use App\Enums\DocumentType;
use App\Enums\ModerationStatus;
use App\Models\PractitionerDocument;
use App\Models\PractitionerProfile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Verification documents (ID, degree, licence) on the PRIVATE disk.
 * Random file names; original name kept only in the database.
 */
class DocumentStore
{
    public const DISK = 'local';

    /** Stores the file and replaces any previous document of the same type. */
    public function store(PractitionerProfile $profile, DocumentType $type, UploadedFile $file): PractitionerDocument
    {
        $ext = match ($file->getMimeType()) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            default => 'jpg',
        };
        $path = Storage::disk(self::DISK)->putFileAs(
            "documents/{$profile->id}",
            $file,
            Str::uuid().'.'.$ext,
        );

        return DB::transaction(function () use ($profile, $type, $file, $path) {
            $old = $profile->documents()->where('type', $type)->get();

            $doc = $profile->documents()->create([
                'type' => $type,
                'path' => $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 180, ''),
                'mime' => $file->getMimeType(),
                'size_bytes' => $file->getSize(),
                'sha256' => hash_file('sha256', $file->getRealPath()),
                'status' => ModerationStatus::Pending,
            ]);

            foreach ($old as $previous) {
                Storage::disk(self::DISK)->delete($previous->path);
                $previous->delete();
            }

            return $doc;
        });
    }

    public function response(PractitionerDocument $document)
    {
        abort_unless(Storage::disk(self::DISK)->exists($document->path), 404);

        return Storage::disk(self::DISK)->response($document->path, $document->original_name, [
            'Content-Type' => $document->mime,
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox",
        ], 'inline');
    }
}
