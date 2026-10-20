<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Admin\Concerns\FiltersAthletes;
use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificateCoverageService;
use App\Services\CsvExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    use FiltersAthletes;

    public function nonCompliant(Request $request, CertificateCoverageService $coverageService): View|StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $athletes = $this->applyAthleteFilters(
            User::query()->where('role', UserRole::Athlete->value)->with(['trainingLocation', 'memberships', 'certificates']),
            $request,
        )->orderBy('last_name')->orderBy('first_name')->get();

        $nonCompliant = $athletes
            ->reject(fn (User $athlete) => $coverageService->isCompliant($athlete))
            ->map(fn (User $athlete) => [
                'user' => $athlete,
                'gaps' => $coverageService->gapsFor($athlete)->map(
                    fn ($gap) => $coverageService->gapDescription($athlete, $gap->requirement)
                ),
            ])
            ->values();

        if ($request->string('export')->toString() === 'csv') {
            return CsvExport::stream('atleti-non-in-regola.csv', ['Nome', 'Cognome', 'Email', 'Motivo'], $nonCompliant->map(fn ($row) => [
                $row['user']->first_name,
                $row['user']->last_name,
                $row['user']->email,
                $row['gaps']->implode('; '),
            ]));
        }

        return view('admin.dashboard-non-compliant', [
            'nonCompliant' => $nonCompliant,
            ...$this->athleteFilterOptions(),
        ]);
    }

    public function expiring(Request $request): View|StreamedResponse
    {
        $this->authorize('viewAny', Certificate::class);

        $query = $this->applyAthleteFiltersViaUser(
            Certificate::query()->valid()->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())->with('user'),
            $request,
        )->orderBy('expires_at');

        if ($request->string('export')->toString() === 'csv') {
            return CsvExport::stream('certificati-in-scadenza.csv', ['Atleta', 'Email', 'Tipo', 'Scadenza'], $query->get()->map(fn (Certificate $c) => [
                $c->user->fullName(),
                $c->user->email,
                $c->typeDescription(),
                $c->expires_at->format('d/m/Y'),
            ]));
        }

        return view('admin.dashboard-expiring', [
            'certificates' => $query->get(),
            ...$this->athleteFilterOptions(),
        ]);
    }
}
