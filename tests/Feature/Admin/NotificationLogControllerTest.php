<?php

use App\Enums\EmailTemplateKey;
use App\Enums\NotificationStatus;
use App\Models\Certificate;
use App\Models\CertificateNotification;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('impedisce a un non amministratore di accedere al log invii', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.notification-log.index'))->assertForbidden();
});

it('mostra gli invii registrati all\'amministratore', function () {
    $certificate = Certificate::factory()->approved()->create();
    CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Sent->value,
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.notification-log.index'))
        ->assertOk()
        ->assertSee($certificate->user->fullName());
});

it('filtra gli invii per stato', function () {
    $certificate = Certificate::factory()->approved()->create();
    CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Sent->value,
    ]);
    CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder10->value,
        'status' => NotificationStatus::Failed->value,
        'error_message' => 'Errore di connessione',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.notification-log.index', ['status' => 'failed']));

    $response->assertOk()->assertSee('Errore di connessione')->assertDontSee('Promemoria scadenza – 30 giorni');
});
