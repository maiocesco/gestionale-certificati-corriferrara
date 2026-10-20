<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Certificate;
use App\Services\CertificateFileStorage;
use App\Services\RetentionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CertificateDeletionController extends Controller
{
    public function index(RetentionService $retentionService): View
    {
        $this->authorize('viewAny', Certificate::class);

        return view('admin.certificate-deletion.index', [
            'certificates' => $retentionService->eligibleForDeletion(),
        ]);
    }

    public function confirmDelete(Certificate $certificate, RetentionService $retentionService): View
    {
        $this->authorize('delete', $certificate);

        abort_unless($retentionService->isEligibleForDeletion($certificate), 404);

        return view('admin.certificate-deletion.confirm-delete', [
            'certificate' => $certificate,
        ]);
    }

    public function destroy(
        Certificate $certificate,
        RetentionService $retentionService,
        CertificateFileStorage $fileStorage,
    ): RedirectResponse {
        $this->authorize('delete', $certificate);

        abort_unless($retentionService->isEligibleForDeletion($certificate), 404);

        $retentionService->delete($certificate, auth()->user(), $fileStorage);

        AuditLog::record(auth()->user(), 'certificate.retention_deleted', $certificate, [
            'owner_id' => $certificate->user_id,
        ]);

        return redirect()->route('admin.certificate-deletion.index')->with('status', 'certificate-deleted');
    }
}
