<?php

namespace App\Http\Controllers;

use App\Enums\CertificateStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\User;
use App\Services\CertificateCoverageService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, CertificateCoverageService $coverageService): View
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return view('dashboard', ['isAdmin' => false]);
        }

        $athletes = User::where('role', UserRole::Athlete->value)->get();

        return view('dashboard', [
            'isAdmin' => true,
            'pendingCount' => Certificate::where('status', CertificateStatus::Pending->value)->count(),
            'nonCompliantCount' => $athletes->reject(fn (User $athlete) => $coverageService->isCompliant($athlete))->count(),
            'expiringCount' => Certificate::valid()->whereDate('expires_at', '<=', now()->addDays(30)->toDateString())->count(),
        ]);
    }
}
