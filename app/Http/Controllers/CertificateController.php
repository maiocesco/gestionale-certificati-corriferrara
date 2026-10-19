<?php

namespace App\Http\Controllers;

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Http\Requests\StoreCertificateRequest;
use App\Services\CertificateCoverageService;
use App\Services\CertificateReviewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateController extends Controller
{
    public function index(Request $request, CertificateCoverageService $coverageService): View
    {
        $user = $request->user();
        $user->load('certificates');

        return view('certificates.index', [
            'validCertificates' => $user->certificates->filter->isValid()->sortBy('expires_at'),
            'pendingCertificates' => $user->certificates->where('status', CertificateStatus::Pending),
            'history' => $user->certificates->sortByDesc('created_at'),
            'coverage' => $coverageService->coverageFor($user),
            'isCompliant' => $coverageService->isCompliant($user),
        ]);
    }

    public function create(Request $request): View
    {
        return view('certificates.create', [
            'hasFidalMembership' => $request->user()->hasFidalMembership(),
            'types' => CertificateType::cases(),
            'disciplines' => Discipline::cases(),
        ]);
    }

    public function store(StoreCertificateRequest $request, CertificateReviewService $reviewService): RedirectResponse
    {
        $user = $request->user();

        $reviewService->upload($user, $user, $request->file('file'), $request->safe()->only(['expires_at', 'type', 'discipline']));

        return redirect()->route('certificates.index')->with('status', 'certificate-uploaded');
    }
}
