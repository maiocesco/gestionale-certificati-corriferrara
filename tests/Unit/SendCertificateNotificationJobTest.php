<?php

use App\Enums\EmailTemplateKey;
use App\Enums\NotificationStatus;
use App\Jobs\SendCertificateNotification;
use App\Mail\CertificateNotificationEmail;
use App\Models\Certificate;
use App\Models\CertificateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('invia l\'email e segna la notifica come inviata', function () {
    Mail::fake();

    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();
    $notification = CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Queued->value,
    ]);

    (new SendCertificateNotification($notification->id))->handle();

    Mail::assertSent(CertificateNotificationEmail::class, fn ($mail) => $mail->certificate->is($certificate));
    expect($notification->fresh()->status)->toBe(NotificationStatus::Sent);
});

it('passa i segnaposto aggiuntivi alla mail', function () {
    Mail::fake();

    $certificate = Certificate::factory()->approved()->expiringInDays(0)->create();
    $notification = CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::PartialSuspension->value,
        'status' => NotificationStatus::Queued->value,
    ]);

    (new SendCertificateNotification($notification->id, ['attivita_consentite' => 'prova']))->handle();

    Mail::assertSent(CertificateNotificationEmail::class);
});

it('segna la notifica come fallita con il messaggio di errore dopo l\'ultimo tentativo', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();
    $notification = CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Queued->value,
    ]);

    (new SendCertificateNotification($notification->id))->failed(new Exception('Connessione SMTP rifiutata'));

    $notification->refresh();
    expect($notification->status)->toBe(NotificationStatus::Failed);
    expect($notification->error_message)->toBe('Connessione SMTP rifiutata');
});
