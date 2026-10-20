<?php

use App\Enums\MembershipEntity;
use App\Models\Certificate;
use App\Models\Membership;
use App\Models\RetentionSetting;
use App\Models\User;
use App\Services\CertificateFileStorage;
use App\Services\RetentionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new RetentionService();
});

// --- Periodo di conservazione in base ai tesseramenti -------------------

it('applica il periodo "nessun tesseramento" a un atleta senza tesseramenti', function () {
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
    $user = User::factory()->create();

    expect($this->service->retentionYearsFor($user))->toBe(10);
});

it('applica il periodo dell\'unico ente a cui l\'atleta è tesserato', function () {
    RetentionSetting::create(['category' => 'fidal', 'years' => 3]);
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['entity' => MembershipEntity::Fidal]);

    expect($this->service->retentionYearsFor($user))->toBe(3);
});

it('applica il periodo più lungo tra più enti di tesseramento', function () {
    RetentionSetting::create(['category' => 'fidal', 'years' => 3]);
    RetentionSetting::create(['category' => 'uisp', 'years' => 10]);
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['entity' => MembershipEntity::Fidal, 'discipline' => \App\Enums\Discipline::AtleticaLeggera]);
    Membership::factory()->for($user)->create(['entity' => MembershipEntity::Uisp, 'discipline' => \App\Enums\Discipline::Nuoto]);

    expect($this->service->retentionYearsFor($user))->toBe(10);
});

it('usa il valore di default se non c\'è un\'impostazione salvata per la categoria', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['entity' => MembershipEntity::Csi]);

    expect($this->service->retentionYearsFor($user))->toBe(5);
});

// --- Idoneità alla cancellazione ----------------------------------------

it('non considera mai eliminabile un certificato in attesa di verifica, qualunque sia la sua età', function () {
    $certificate = Certificate::factory()->create(['expires_at' => now()->subYears(20)->toDateString()]);

    expect($this->service->isEligibleForDeletion($certificate))->toBeFalse();
});

it('non considera mai eliminabile un certificato valido', function () {
    $certificate = Certificate::factory()->approved()->expiringInDays(10)->create();

    expect($this->service->isEligibleForDeletion($certificate))->toBeFalse();
});

it('non considera eliminabile un certificato scaduto entro il periodo di conservazione', function () {
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
    $certificate = Certificate::factory()->approved()->create(['expires_at' => now()->subYears(5)->toDateString()]);

    expect($this->service->isEligibleForDeletion($certificate))->toBeFalse();
});

it('considera eliminabile un certificato scaduto oltre il periodo di conservazione', function () {
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
    $certificate = Certificate::factory()->approved()->create(['expires_at' => now()->subYears(11)->toDateString()]);

    expect($this->service->isEligibleForDeletion($certificate))->toBeTrue();
});

it('considera eliminabile un certificato rifiutato oltre il periodo di conservazione', function () {
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
    $certificate = Certificate::factory()->rejected()->create(['expires_at' => now()->subYears(11)->toDateString()]);

    expect($this->service->isEligibleForDeletion($certificate))->toBeTrue();
});

it('non considera eliminabile un certificato già eliminato per conservazione', function () {
    RetentionSetting::create(['category' => 'none', 'years' => 10]);
    $certificate = Certificate::factory()->approved()->create([
        'expires_at' => now()->subYears(11)->toDateString(),
        'deleted_at' => now(),
    ]);

    expect($this->service->isEligibleForDeletion($certificate))->toBeFalse();
});

it('rispetta il periodo specifico dell\'atleta nell\'elenco dei certificati eliminabili', function () {
    RetentionSetting::create(['category' => 'fidal', 'years' => 3]);
    RetentionSetting::create(['category' => 'none', 'years' => 10]);

    $fidalUser = User::factory()->create();
    Membership::factory()->for($fidalUser)->create(['entity' => MembershipEntity::Fidal]);
    $oldEnoughForFidal = Certificate::factory()->for($fidalUser)->approved()->create(['expires_at' => now()->subYears(4)->toDateString()]);

    $noMembershipUser = User::factory()->create();
    $notOldEnoughWithoutMembership = Certificate::factory()->for($noMembershipUser)->approved()->create(['expires_at' => now()->subYears(4)->toDateString()]);

    $eligible = $this->service->eligibleForDeletion();

    expect($eligible->pluck('id'))->toContain($oldEnoughForFidal->id);
    expect($eligible->pluck('id'))->not->toContain($notOldEnoughWithoutMembership->id);
});

// --- Eliminazione --------------------------------------------------------

it('cancella il file e riduce il certificato al record minimo', function () {
    Storage::fake('local');
    $file = UploadedFile::fake()->create('certificato.pdf', 100, 'application/pdf');
    Storage::disk('local')->putFileAs('certificates/1', $file, 'test.pdf');

    $admin = User::factory()->admin()->create();
    $certificate = Certificate::factory()->rejected()->create([
        'expires_at' => now()->subYears(20)->toDateString(),
        'file_path' => 'certificates/1/test.pdf',
        'file_original_name' => 'certificato.pdf',
        'file_mime' => 'application/pdf',
        'file_size' => 100,
        'rejection_reason' => 'motivo riservato',
    ]);

    $this->service->delete($certificate, $admin, new CertificateFileStorage());

    Storage::disk('local')->assertMissing('certificates/1/test.pdf');

    $certificate->refresh();
    expect($certificate->file_path)->toBeNull();
    expect($certificate->file_original_name)->toBeNull();
    expect($certificate->file_mime)->toBeNull();
    expect($certificate->file_size)->toBeNull();
    expect($certificate->rejection_reason)->toBeNull();
    expect($certificate->deleted_at)->not->toBeNull();
    expect($certificate->deleted_by)->toBe($admin->id);
    expect($certificate->user_id)->not->toBeNull();
    expect($certificate->expires_at)->not->toBeNull();
});
