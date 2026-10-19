<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CertificateFileController extends Controller
{
    /**
     * Anteprima del file del certificato (immagine/PDF) nel browser,
     * tramite rotta protetta da Policy. Ogni accesso viene registrato
     * nell'audit log (dato sanitario, art. 9 GDPR).
     */
    public function show(Certificate $certificate): Response
    {
        $this->authorize('viewFile', $certificate);

        AuditLog::record(auth()->user(), 'certificate.file.viewed', $certificate, [
            'owner_id' => $certificate->user_id,
        ]);

        $contents = Storage::disk('local')->get($certificate->file_path);

        return response($contents, 200, [
            'Content-Type' => $certificate->file_mime,
            'Content-Disposition' => 'inline; filename="'.$certificate->file_original_name.'"',
        ]);
    }
}
