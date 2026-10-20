<?php

use App\Enums\Discipline;
use App\Enums\EmailTemplateKey;
use App\Enums\NotificationStatus;
use App\Jobs\SendCertificateNotification;
use App\Models\Certificate;
use App\Models\CertificateNotification;
use App\Models\Membership;
use App\Models\User;
use App\Services\CertificateCoverageService;
use App\Services\ReminderSchedulingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new ReminderSchedulingService(new CertificateCoverageService());
    Bus::fake();
});

function assertNotificationQueued(Certificate $certificate, EmailTemplateKey $type): void
{
    expect(CertificateNotification::where('certificate_id', $certificate->id)
        ->where('type', $type->value)
        ->where('status', NotificationStatus::Queued->value)
        ->exists())->toBeTrue();
}

// --- Soglie base --------------------------------------------------------

it('non invia nulla se la scadenza è lontana più di 30 giorni', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(35)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(0);
    expect(CertificateNotification::count())->toBe(0);
    Bus::assertNotDispatched(SendCertificateNotification::class);
});

it('invia il promemoria a 30 giorni quando mancano esattamente 30 giorni', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(1);
    assertNotificationQueued($certificate, EmailTemplateKey::Reminder30);
    Bus::assertDispatched(SendCertificateNotification::class);
});

it('non invia due volte lo stesso promemoria già inviato', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();
    CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Sent->value,
    ]);

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(0);
    Bus::assertNotDispatched(SendCertificateNotification::class);
});

it('non duplica un invio già in coda nella stessa esecuzione o in una successiva', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();
    CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Queued->value,
    ]);

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(0);
});

it('invia il promemoria a 10 giorni e non ripete quello a 30 già inviato', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(10)->create();
    CertificateNotification::create([
        'certificate_id' => $certificate->id,
        'type' => EmailTemplateKey::Reminder30->value,
        'status' => NotificationStatus::Sent->value,
    ]);

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(1);
    assertNotificationQueued($certificate, EmailTemplateKey::Reminder10);
});

it('invia il promemoria a 3 giorni', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(3)->create();

    $this->service->sendDueNotifications();

    assertNotificationQueued($certificate, EmailTemplateKey::Reminder3);
});

// --- Giorno della scadenza: diffida o sospensione parziale -------------

it('invia la diffida il giorno della scadenza se non resta alcuna copertura', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(0)->create();

    $this->service->sendDueNotifications();

    assertNotificationQueued($certificate, EmailTemplateKey::ExpiryNotice);
});

it('invia la sospensione parziale se resta un\'altra copertura valida', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    $expiring = Certificate::factory()->for($user)->competitive(Discipline::Nuoto)->approved()->expiringInDays(0)->create();
    Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->expiringInDays(90)->create();

    $this->service->sendDueNotifications();

    assertNotificationQueued($expiring, EmailTemplateKey::PartialSuspension);

    Bus::assertDispatched(SendCertificateNotification::class, function (SendCertificateNotification $job) {
        return str_contains($job->extraPlaceholders['attivita_consentite'] ?? '', 'atletica leggera');
    });
});

it('considera scaduto anche un certificato scaduto da giorni se non ha mai ricevuto un avviso', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(-5)->create();

    $this->service->sendDueNotifications();

    assertNotificationQueued($certificate, EmailTemplateKey::ExpiryNotice);
});

// --- Recupero dopo un'interruzione: solo la tappa più urgente ----------

it('dopo un salto di più soglie invia solo l\'avviso più urgente, non tutti quelli superati', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(2)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(1);
    assertNotificationQueued($certificate, EmailTemplateKey::Reminder3);
    expect(CertificateNotification::where('certificate_id', $certificate->id)->where('type', EmailTemplateKey::Reminder30->value)->exists())->toBeFalse();
    expect(CertificateNotification::where('certificate_id', $certificate->id)->where('type', EmailTemplateKey::Reminder10->value)->exists())->toBeFalse();
});

it('dopo un\'interruzione fino a oltre la scadenza invia solo l\'avviso di scadenza, non i promemoria superati', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(-2)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(1);
    assertNotificationQueued($certificate, EmailTemplateKey::ExpiryNotice);
    expect(CertificateNotification::where('certificate_id', $certificate->id)->whereIn('type', [
        EmailTemplateKey::Reminder30->value, EmailTemplateKey::Reminder10->value, EmailTemplateKey::Reminder3->value,
    ])->exists())->toBeFalse();
});

it('passa alla tappa successiva un giorno alla volta se lo scheduler gira regolarmente', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(30)->create();

    $this->service->sendDueNotifications();
    assertNotificationQueued($certificate, EmailTemplateKey::Reminder30);
    CertificateNotification::where('certificate_id', $certificate->id)->update(['status' => NotificationStatus::Sent->value]);

    $certificate->update(['expires_at' => now()->addDays(10)->toDateString()]);
    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(1);
    assertNotificationQueued($certificate, EmailTemplateKey::Reminder10);
});

// --- Certificati superati, non approvati, atleti disattivati -----------

it('non invia avvisi per un certificato superato da uno più recente', function () {
    $user = User::factory()->create();
    $old = Certificate::factory()->for($user)->approved()->expiringInDays(2)->create();
    Certificate::factory()->for($user)->approved()->expiringInDays(200)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(0);
    expect(CertificateNotification::where('certificate_id', $old->id)->exists())->toBeFalse();
});

it('non invia avvisi per certificati in attesa o rifiutati', function () {
    Certificate::factory()->expiringInDays(0)->create(); // pending di default
    Certificate::factory()->rejected()->expiringInDays(0)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(0);
});

it('non invia avvisi a un atleta disattivato', function () {
    $user = User::factory()->disabled()->create();
    Certificate::factory()->for($user)->approved()->expiringInDays(3)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(0);
});

// --- Più certificati per lo stesso atleta -------------------------------

it('traccia separatamente gli avvisi di certificati diversi dello stesso atleta', function () {
    $user = User::factory()->create();
    $swim = Certificate::factory()->for($user)->competitive(Discipline::Nuoto)->approved()->expiringInDays(30)->create();
    $athletics = Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->expiringInDays(10)->create();

    $count = $this->service->sendDueNotifications();

    expect($count)->toBe(2);
    assertNotificationQueued($swim, EmailTemplateKey::Reminder30);
    assertNotificationQueued($athletics, EmailTemplateKey::Reminder10);
});
