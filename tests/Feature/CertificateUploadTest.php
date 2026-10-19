<?php

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->athlete = User::factory()->create();
});

function validCertificatePayload(array $overrides = []): array
{
    return array_merge([
        'expires_at' => now()->addMonths(6)->toDateString(),
        'type' => CertificateType::NonCompetitive->value,
        'file' => UploadedFile::fake()->create('certificato.pdf', 500, 'application/pdf'),
    ], $overrides);
}

it('permette a un atleta di caricare un proprio certificato', function () {
    $response = $this->actingAs($this->athlete)->post(route('certificates.store'), validCertificatePayload());

    $response->assertRedirect(route('certificates.index'));

    $certificate = $this->athlete->certificates()->first();
    expect($certificate)->not->toBeNull();
    expect($certificate->status)->toBe(CertificateStatus::Pending);
    Storage::disk('local')->assertExists($certificate->file_path);
});

it('richiede la disciplina solo per i certificati agonistici', function () {
    $response = $this->actingAs($this->athlete)->post(
        route('certificates.store'),
        validCertificatePayload(['type' => CertificateType::Competitive->value])
    );

    $response->assertSessionHasErrors('discipline');
});

it('rifiuta una data di scadenza non futura', function () {
    $response = $this->actingAs($this->athlete)->post(
        route('certificates.store'),
        validCertificatePayload(['expires_at' => now()->toDateString()])
    );

    $response->assertSessionHasErrors('expires_at');
});

it('rifiuta un file di tipo non ammesso', function () {
    $response = $this->actingAs($this->athlete)->post(
        route('certificates.store'),
        validCertificatePayload(['file' => UploadedFile::fake()->create('certificato.exe', 500, 'application/x-msdownload')])
    );

    $response->assertSessionHasErrors('file');
});

it('rifiuta un file troppo grande', function () {
    $tooBig = (config('certificates.max_size_mb') + 1) * 1024;

    $response = $this->actingAs($this->athlete)->post(
        route('certificates.store'),
        validCertificatePayload(['file' => UploadedFile::fake()->create('certificato.pdf', $tooBig, 'application/pdf')])
    );

    $response->assertSessionHasErrors('file');
});

it('blocca il caricamento di un certificato non agonistico per un atleta FIDAL', function () {
    Membership::factory()->fidal()->for($this->athlete)->create();

    $response = $this->actingAs($this->athlete)->post(
        route('certificates.store'),
        validCertificatePayload(['type' => CertificateType::NonCompetitive->value])
    );

    $response->assertSessionHasErrors('type');
});

it('permette a un atleta FIDAL di caricare un agonistico per atletica leggera', function () {
    Membership::factory()->fidal()->for($this->athlete)->create();

    $response = $this->actingAs($this->athlete)->post(
        route('certificates.store'),
        validCertificatePayload([
            'type' => CertificateType::Competitive->value,
            'discipline' => Discipline::AtleticaLeggera->value,
        ])
    );

    $response->assertSessionHasNoErrors();
});

it('non permette di caricare un certificato per conto di un altro atleta', function () {
    $other = User::factory()->create();

    $response = $this->actingAs($this->athlete)->post(
        route('admin.users.certificates.store', $other),
        validCertificatePayload()
    );

    $response->assertForbidden();
});
