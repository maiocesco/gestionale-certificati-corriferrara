<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CertificateStatus;
use App\Http\Controllers\Admin\Concerns\FiltersAthletes;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApproveCertificateRequest;
use App\Http\Requests\Admin\RejectCertificateRequest;
use App\Http\Requests\StoreCertificateRequest;
use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificateReviewService;
use App\Services\CsvExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    use FiltersAthletes;

    public function index(Request $request): View|StreamedResponse
    {
        $this->authorize('viewAny', Certificate::class);

        $status = $request->string('status')->toString();
        $query = $this->filteredQuery($status, $request);

        if ($request->string('export')->toString() === 'csv') {
            return CsvExport::stream('certificati.csv', ['Atleta', 'Email', 'Tipo', 'Scadenza', 'Stato'], $query->get()->map(fn (Certificate $c) => [
                $c->user->fullName(),
                $c->user->email,
                $c->typeDescription(),
                $c->expires_at->format('d/m/Y'),
                $c->status->label(),
            ]));
        }

        $certificates = $query->paginate(20)->withQueryString();

        return view('admin.certificates.index', [
            'certificates' => $certificates,
            'status' => $status,
            'statuses' => CertificateStatus::cases(),
            ...$this->athleteFilterOptions(),
        ]);
    }

    private function filteredQuery(string $status, Request $request): Builder
    {
        $query = Certificate::query()
            ->whereNull('deleted_at')
            ->with(['user', 'uploadedBy'])
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($status === '', fn ($query) => $query->where('status', CertificateStatus::Pending->value))
            ->orderBy('created_at');

        return $this->applyAthleteFiltersViaUser($query, $request);
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
