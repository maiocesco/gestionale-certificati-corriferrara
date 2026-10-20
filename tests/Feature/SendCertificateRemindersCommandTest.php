<?php

use App\Enums\EmailTemplateKey;
use App\Enums\NotificationStatus;
use App\Jobs\SendCertificateNotification;
use App\Models\Certificate;
use App\Models\CertificateNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

it('accoda gli avvisi dovuti quando il comando schedulato gira', function () {
    Bus::fake();

    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();

    $this->artisan('certificates:send-reminders')->assertSuccessful();

    expect(CertificateNotification::where('certificate_id', $certificate->id)
        ->where('type', EmailTemplateKey::Reminder30->value)
        ->where('status', NotificationStatus::Queued->value)
        ->exists())->toBeTrue();

    Bus::assertDispatched(SendCertificateNotification::class);
});

it('non accoda nulla se nessun certificato è in scadenza', function () {
    Bus::fake();

    Certificate::factory()->approved()->expiringInDays(90)->create();

    $this->artisan('certificates:send-reminders')->assertSuccessful();

    Bus::assertNotDispatched(SendCertificateNotification::class);
});
