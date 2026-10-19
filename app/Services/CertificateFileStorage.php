<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Salvataggio dei file dei certificati su disco privato
 * (storage/app/private, mai accessibile pubblicamente), con nomi non
 * prevedibili, come richiesto per dati sanitari (art. 9 GDPR).
 */
class CertificateFileStorage
{
    public function store(UploadedFile $file, User $owner): array
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = "certificates/{$owner->id}/{$filename}";

        Storage::disk('local')->putFileAs(
            "certificates/{$owner->id}",
            $file,
            $filename,
        );

        return [
            'file_path' => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public function delete(string $path): void
    {
        Storage::disk('local')->delete($path);
    }
}
