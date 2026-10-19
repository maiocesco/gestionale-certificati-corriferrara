<?php

namespace App\Services;

use App\Enums\CertificateStatus;
use App\Mail\CertificateApprovedEmail;
use App\Mail\CertificateRejectedEmail;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

class CertificateReviewService
{
    public function __construct(
        private readonly CertificateFileStorage $fileStorage,
    ) {}

    public function upload(User $owner, User $uploadedBy, UploadedFile $file, array $data): Certificate
    {
        $fileData = $this->fileStorage->store($file, $owner);

        return Certificate::create([
            ...$data,
            ...$fileData,
            'user_id' => $owner->id,
            'uploaded_by' => $uploadedBy->id,
            'status' => CertificateStatus::Pending,
        ]);
    }

    public function approve(Certificate $certificate, User $reviewer, array $corrections): Certificate
    {
        $certificate->update([
            ...$corrections,
            'status' => CertificateStatus::Approved,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'rejection_reason' => null,
        ]);

        Mail::to($certificate->user->email)->send(new CertificateApprovedEmail($certificate));

        return $certificate;
    }

    public function reject(Certificate $certificate, User $reviewer, string $reason): Certificate
    {
        $certificate->update([
            'status' => CertificateStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        Mail::to($certificate->user->email)->send(new CertificateRejectedEmail($certificate));

        return $certificate;
    }
}
