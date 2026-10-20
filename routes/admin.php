<?php

use App\Http\Controllers\Admin\CadastralCodeController;
use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CertificateDeletionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmailAccountController;
use App\Http\Controllers\Admin\EmailTemplateController;
use App\Http\Controllers\Admin\ExternalClubController;
use App\Http\Controllers\Admin\MembershipController;
use App\Http\Controllers\Admin\NotificationLogController;
use App\Http\Controllers\Admin\RetentionSettingController;
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

    Route::get('email-accounts', [EmailAccountController::class, 'index'])->name('email-accounts.index');
    Route::put('email-accounts/{purpose}', [EmailAccountController::class, 'update'])->name('email-accounts.update');
    Route::post('email-accounts/{purpose}/test', [EmailAccountController::class, 'sendTest'])->name('email-accounts.test');

    Route::get('email-templates', [EmailTemplateController::class, 'index'])->name('email-templates.index');
    Route::get('email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
    // POST (non PUT): la pagina di modifica ha un secondo pulsante "Anteprima"
    // che punta a un'altra rotta tramite formaction/formmethod; un override
    // _method condiviso dal form avrebbe spoofato anche quella richiesta.
    Route::post('email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
    Route::post('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview');
    Route::post('email-templates/{emailTemplate}/test', [EmailTemplateController::class, 'sendTest'])->name('email-templates.test');
    Route::put('email-templates/{emailTemplate}/reset', [EmailTemplateController::class, 'reset'])->name('email-templates.reset');

    Route::get('notification-log', [NotificationLogController::class, 'index'])->name('notification-log.index');

    Route::get('retention-settings', [RetentionSettingController::class, 'index'])->name('retention-settings.index');
    Route::put('retention-settings/{category}', [RetentionSettingController::class, 'update'])->name('retention-settings.update');

    Route::get('certificate-deletion', [CertificateDeletionController::class, 'index'])->name('certificate-deletion.index');
    Route::get('certificate-deletion/{certificate}', [CertificateDeletionController::class, 'confirmDelete'])->name('certificate-deletion.confirm-delete');
    Route::delete('certificate-deletion/{certificate}', [CertificateDeletionController::class, 'destroy'])->name('certificate-deletion.destroy');
});
