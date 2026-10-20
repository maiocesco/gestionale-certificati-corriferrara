<?php

use App\Enums\CertificateStatus;
use App\Mail\AdminDailyDigestEmail;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('invia il riepilogo solo agli amministratori che l\'hanno attivato', function () {
    Mail::fake();

    $subscribed = User::factory()->admin()->create(['daily_digest_enabled' => true]);
    User::factory()->admin()->create(['daily_digest_enabled' => false]);
    Certificate::factory()->create(['status' => CertificateStatus::Pending]);

    $this->artisan('admins:send-daily-digest')->assertSuccessful();

    Mail::assertQueued(AdminDailyDigestEmail::class, fn ($mail) => $mail->admin->is($subscribed));
    Mail::assertQueuedCount(1);
});

it('non invia nulla se non ci sono scadenze né certificati da approvare', function () {
    Mail::fake();

    User::factory()->admin()->create(['daily_digest_enabled' => true]);

    $this->artisan('admins:send-daily-digest')->assertSuccessful();

    Mail::assertNothingSent();
});

it('non invia nulla a un amministratore disattivato anche se ha il riepilogo attivo', function () {
    Mail::fake();

    User::factory()->admin()->disabled()->create(['daily_digest_enabled' => true]);
    Certificate::factory()->create(['status' => CertificateStatus::Pending]);

    $this->artisan('admins:send-daily-digest')->assertSuccessful();

    Mail::assertNothingSent();
});
