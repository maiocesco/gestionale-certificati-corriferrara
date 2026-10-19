<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveCertificateRequest;
use App\Http\Requests\Admin\RejectCertificateRequest;
use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificateReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Certificate::class);

        $status = $request->string('status')->toString();

        $certificates = Certificate::query()
            ->with(['user', 'uploadedBy'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($status === '', fn ($query) => $query->where('status', CertificateStatus::Pending->value))
            ->orderBy('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.certificates.index', [
            'certificates' => $certificates,
            'status' => $status,
            'statuses' => CertificateStatus::cases(),
        ]);
    }

    public function store(StoreCertificateRequest $request, User $user, CertificateReviewService $reviewService): RedirectResponse
    {
        $reviewService->upload($user, $request->user(), $request->file('file'), $request->safe()->only(['expires_at', 'type', 'discipline']));

        return redirect()->route('admin.users.edit', $user)->with('status', 'certificate-uploaded');
    }

    public function approve(ApproveCertificateRequest $request, Certificate $certificate, CertificateReviewService $reviewService): RedirectResponse
    {
        $reviewService->approve($certificate, $request->user(), $request->validated());

        return redirect()->back()->with('status', 'certificate-approved');
    }

    public function reject(RejectCertificateRequest $request, Certificate $certificate, CertificateReviewService $reviewService): RedirectResponse
    {
        $reviewService->reject($certificate, $request->user(), $request->validated('rejection_reason'));

        return redirect()->back()->with('status', 'certificate-rejected');
    }
}
