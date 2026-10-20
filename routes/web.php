<?php

use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateFileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/digest-preference', [ProfileController::class, 'updateDigestPreference'])->name('profile.digest-preference.update');

    Route::get('/certificates', [CertificateController::class, 'index'])->name('certificates.index');
    Route::get('/certificates/create', [CertificateController::class, 'create'])->name('certificates.create');
    Route::post('/certificates', [CertificateController::class, 'store'])->name('certificates.store');
    Route::get('/certificates/{certificate}/file', [CertificateFileController::class, 'show'])->name('certificates.file');
});

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
