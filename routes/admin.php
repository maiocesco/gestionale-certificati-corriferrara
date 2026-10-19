<?php

use App\Http\Controllers\Admin\CadastralCodeController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ExternalClubController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\TrainingLocationController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::put('users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

    Route::post('users/{user}/memberships', [MembershipController::class, 'store'])->name('users.memberships.store');
    Route::put('users/{user}/memberships/{membership}', [MembershipController::class, 'update'])->name('users.memberships.update');
    Route::delete('users/{user}/memberships/{membership}', [MembershipController::class, 'destroy'])->name('users.memberships.destroy');

    Route::get('cadastral-codes/search', [CadastralCodeController::class, 'search'])->name('cadastral-codes.search');
    Route::get('external-clubs/search', [ExternalClubController::class, 'search'])->name('external-clubs.search');

    Route::get('training-locations', [TrainingLocationController::class, 'index'])->name('training-locations.index');
    Route::get('training-locations/create', [TrainingLocationController::class, 'create'])->name('training-locations.create');
    Route::post('training-locations', [TrainingLocationController::class, 'store'])->name('training-locations.store');
    Route::get('training-locations/{training_location}/edit', [TrainingLocationController::class, 'edit'])->name('training-locations.edit');
    Route::put('training-locations/{training_location}', [TrainingLocationController::class, 'update'])->name('training-locations.update');
    Route::get('training-locations/{training_location}/delete', [TrainingLocationController::class, 'confirmDelete'])->name('training-locations.confirm-delete');
    Route::delete('training-locations/{training_location}', [TrainingLocationController::class, 'destroy'])->name('training-locations.destroy');

    Route::get('certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::post('users/{user}/certificates', [CertificateController::class, 'store'])->name('users.certificates.store');
    Route::put('certificates/{certificate}/approve', [CertificateController::class, 'approve'])->name('certificates.approve');
    Route::put('certificates/{certificate}/reject', [CertificateController::class, 'reject'])->name('certificates.reject');

    Route::get('dashboard/non-compliant', [DashboardController::class, 'nonCompliant'])->name('dashboard.non-compliant');
    Route::get('dashboard/expiring', [DashboardController::class, 'expiring'])->name('dashboard.expiring');
});
