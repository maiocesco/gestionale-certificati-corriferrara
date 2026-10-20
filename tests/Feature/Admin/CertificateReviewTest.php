<?php

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Mail\CertificateApprovedEmail;
use App\Mail\CertificateRejectedEmail;
use App\Models\Certificate;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->athlete = User::factory()->create();
});

it('impedisce a un non amministratore di revisionare certificati', function () {
    $certificate = Certificate::factory()->for($this->athlete)->create();
    $other = User::factory()->create();

    $this->actingAs($other)->put(route('admin.certificates.approve', $certificate), [
        'expires_at' => now()->addMonths(6)->toDateString(),
        'type' => CertificateType::NonCompetitive->value,
    ])->assertForbidden();
});

it('approva un certificato e invia l\'email', function () {
    Mail::fake();
    $certificate = Certificate::factory()->for($this->athlete)->create();

    $response = $this->actingAs($this->admin)->put(route('admin.certificates.approve', $certificate), [
        'expires_at' => now()->addMonths(6)->toDateString(),
        'type' => CertificateType::NonCompetitive->value,
    ]);

    $response->assertRedirect();
    $certificate->refresh();
    expect($certificate->status)->toBe(CertificateStatus::Approved);
    expect($certificate->reviewed_by)->toBe($this->admin->id);
    expect($certificate->reviewed_at)->not->toBeNull();

    Mail::assertQueued(CertificateApprovedEmail::class, fn ($mail) => $mail->certificate->is($certificate));
});

it('permette all\'amministratore di correggere i dati in approvazione', function () {
    $certificate = Certificate::factory()->for($this->athlete)->create([
        'type' => CertificateType::NonCompetitive,
        'discipline' => null,
    ]);

    $this->actingAs($this->admin)->put(route('admin.certificates.approve', $certificate), [
        'expires_at' => now()->addYear()->toDateString(),
        'type' => CertificateType::Competitive->value,
        'discipline' => Discipline::Nuoto->value,
    ]);

    $certificate->refresh();
    expect($certificate->type)->toBe(CertificateType::Competitive);
    expect($certificate->discipline)->toBe(Discipline::Nuoto);
});

it('impedisce di approvare come non agonistico un certificato di un atleta FIDAL', function () {
    Membership::factory()->fidal()->for($this->athlete)->create();
    $certificate = Certificate::factory()->for($this->athlete)->competitive(Discipline::AtleticaLeggera)->create();

    $response = $this->actingAs($this->admin)->put(route('admin.certificates.approve', $certificate), [
        'expires_at' => now()->addMonths(6)->toDateString(),
        'type' => CertificateType::NonCompetitive->value,
    ]);

    $response->assertSessionHasErrors('type');
    expect($certificate->refresh()->status)->toBe(CertificateStatus::Pending);
});

it('rifiuta un certificato con motivazione obbligatoria e invia l\'email', function () {
    Mail::fake();
    $certificate = Certificate::factory()->for($this->athlete)->create();

    $response = $this->actingAs($this->admin)->put(route('admin.certificates.reject', $certificate), [
        'rejection_reason' => 'Foto illeggibile, carica un documento più nitido.',
    ]);

    $response->assertRedirect();
    $certificate->refresh();
    expect($certificate->status)->toBe(CertificateStatus::Rejected);
    expect($certificate->rejection_reason)->toBe('Foto illeggibile, carica un documento più nitido.');

    Mail::assertQueued(CertificateRejectedEmail::class, fn ($mail) => $mail->certificate->is($certificate));
});

it('non permette di rifiutare un certificato senza motivazione', function () {
    $certificate = Certificate::factory()->for($this->athlete)->create();

    $response = $this->actingAs($this->admin)->put(route('admin.certificates.reject', $certificate), []);

    $response->assertSessionHasErrors('rejection_reason');
    expect($certificate->refresh()->status)->toBe(CertificateStatus::Pending);
});

it('mostra l\'elenco dei certificati in attesa per default', function () {
    $pending = Certificate::factory()->for($this->athlete)->create();
    $approved = Certificate::factory()->for($this->athlete)->approved()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.certificates.index'));

    $response->assertOk();
    $response->assertSee($this->athlete->fullName());
});

it('non mostra un certificato eliminato per conservazione', function () {
    $deleted = Certificate::factory()->for($this->athlete)->rejected()->create([
        'expires_at' => now()->subYears(11)->toDateString(),
        'deleted_at' => now(),
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['status' => 'rejected']));

    $response->assertOk();
    expect($response->viewData('certificates')->pluck('id'))->not->toContain($deleted->id);
});

it('filtra l\'elenco certificati per ente di tesseramento', function () {
    $fidalAthlete = User::factory()->create(['first_name' => 'Fidal', 'last_name' => 'Atleta']);
    Membership::factory()->for($fidalAthlete)->create(['entity' => MembershipEntity::Fidal]);
    Certificate::factory()->for($fidalAthlete)->create();

    $uispAthlete = User::factory()->create(['first_name' => 'Uisp', 'last_name' => 'Atleta']);
    Membership::factory()->for($uispAthlete)->create(['entity' => MembershipEntity::Uisp]);
    Certificate::factory()->for($uispAthlete)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['entity' => 'fidal']));

    $response->assertOk()->assertSee('Fidal Atleta')->assertDontSee('Uisp Atleta');
});

it('esporta in csv l\'elenco certificati', function () {
    Certificate::factory()->for($this->athlete)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.certificates.index', ['export' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});
