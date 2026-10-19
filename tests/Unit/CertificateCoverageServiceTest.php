<?php

use App\Enums\Discipline;
use App\Models\Certificate;
use App\Models\Membership;
use App\Models\User;
use App\Services\CertificateCoverageService;

beforeEach(function () {
    $this->service = new CertificateCoverageService();
});

// --- Requisiti --------------------------------------------------------

it('richiede solo il requisito generico per un atleta senza tesseramenti', function () {
    $user = User::factory()->create();

    $requirements = $this->service->requirementsFor($user);

    expect($requirements)->toHaveCount(1);
    expect($requirements->first()->isAny())->toBeTrue();
});

it('richiede solo il requisito generico per tesseramenti non agonistici', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => false, 'discipline' => Discipline::AtleticaLeggera]);

    $requirements = $this->service->requirementsFor($user);

    expect($requirements)->toHaveCount(1);
    expect($requirements->first()->isAny())->toBeTrue();
});

it('aggiunge un requisito agonistico per ogni disciplina con tesseramento agonistico', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::Nuoto, 'entity' => \App\Enums\MembershipEntity::Uisp]);

    $requirements = $this->service->requirementsFor($user);

    expect($requirements)->toHaveCount(3); // atletica + nuoto + any
    expect($requirements->filter->isAny())->toHaveCount(1);
});

it('non duplica il requisito se più tesseramenti agonistici condividono la disciplina', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Uisp]);

    $requirements = $this->service->requirementsFor($user);

    expect($requirements)->toHaveCount(2); // atletica (una sola volta) + any
});

// --- Copertura: casi base ---------------------------------------------

it('non è in regola un atleta senza tesseramenti e senza certificati', function () {
    $user = User::factory()->create();

    expect($this->service->isCompliant($user))->toBeFalse();
});

it('è in regola un atleta senza tesseramenti con un certificato non agonistico valido', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->approved()->create();

    expect($this->service->isCompliant($user))->toBeTrue();
});

it('un agonistico di qualunque disciplina copre anche il requisito generico', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->competitive(Discipline::Nuoto)->approved()->create();

    expect($this->service->isCompliant($user))->toBeTrue();
});

it('non è in regola un atleta agonista senza il certificato per la sua disciplina', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);

    expect($this->service->isCompliant($user))->toBeFalse();
    expect($this->service->gapsFor($user))->toHaveCount(2); // agonistico atletica + any
});

it('è in regola un atleta agonista con il certificato agonistico della sua disciplina', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->create();

    expect($this->service->isCompliant($user))->toBeTrue();
});

it('serve un agonistico per ogni disciplina: chi fa 3 sport ne serve 3', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::Ciclismo, 'entity' => \App\Enums\MembershipEntity::Uisp]);
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::Nuoto, 'entity' => \App\Enums\MembershipEntity::Csi]);

    Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->create();
    Certificate::factory()->for($user)->competitive(Discipline::Ciclismo)->approved()->create();

    expect($this->service->isCompliant($user))->toBeFalse();
    expect($this->service->gapsFor($user))->toHaveCount(1);

    Certificate::factory()->for($user)->competitive(Discipline::Nuoto)->approved()->create();

    expect($this->service->isCompliant($user))->toBeTrue();
});

it('lo stesso certificato copre tesseramenti di enti diversi nella stessa disciplina', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Uisp]);
    Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->create();

    expect($this->service->isCompliant($user))->toBeTrue();
});

// --- Stati del certificato ---------------------------------------------

it('un certificato in attesa non conta ai fini della copertura', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->create(); // pending di default

    expect($this->service->isCompliant($user))->toBeFalse();
});

it('un certificato rifiutato non conta ai fini della copertura', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->rejected()->create();

    expect($this->service->isCompliant($user))->toBeFalse();
});

it('un certificato scaduto non conta ai fini della copertura', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->approved()->expired()->create();

    expect($this->service->isCompliant($user))->toBeFalse();
});

// --- Date limite ---------------------------------------------------------

it('un certificato che scade oggi è già considerato non valido', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->approved()->create(['expires_at' => now()->toDateString()]);

    expect($this->service->isCompliant($user))->toBeFalse();
});

it('un certificato che scade domani è ancora valido', function () {
    $user = User::factory()->create();
    Certificate::factory()->for($user)->approved()->create(['expires_at' => now()->addDay()->toDateString()]);

    expect($this->service->isCompliant($user))->toBeTrue();
});

// --- Certificato superato ------------------------------------------------

it('un certificato approvato con scadenza precedente è superato da uno più recente', function () {
    $user = User::factory()->create();
    $old = Certificate::factory()->for($user)->approved()->create(['expires_at' => now()->addMonths(2)->toDateString()]);
    $new = Certificate::factory()->for($user)->approved()->create(['expires_at' => now()->addMonths(6)->toDateString()]);

    expect($this->service->isSuperseded($old))->toBeTrue();
    expect($this->service->isSuperseded($new))->toBeFalse();
});

it('un certificato non è superato se è l\'unico nel suo ambito', function () {
    $user = User::factory()->create();
    $certificate = Certificate::factory()->for($user)->approved()->create();

    expect($this->service->isSuperseded($certificate))->toBeFalse();
});

it('un certificato in attesa o rifiutato non è mai "superato"', function () {
    $user = User::factory()->create();
    $pending = Certificate::factory()->for($user)->create();
    $rejected = Certificate::factory()->for($user)->rejected()->create();
    Certificate::factory()->for($user)->approved()->create();

    expect($this->service->isSuperseded($pending))->toBeFalse();
    expect($this->service->isSuperseded($rejected))->toBeFalse();
});

it('certificati di ambiti diversi non si superano a vicenda', function () {
    $user = User::factory()->create();
    $nonCompetitive = Certificate::factory()->for($user)->approved()->create();
    $competitive = Certificate::factory()->for($user)->competitive(Discipline::Nuoto)->approved()->create();

    expect($this->service->isSuperseded($nonCompetitive))->toBeFalse();
    expect($this->service->isSuperseded($competitive))->toBeFalse();
});

// --- Copertura residua (per diffida/sospensione parziale, Fase 6) --------

it('nessuna copertura residua se l\'unico certificato valido viene rimosso', function () {
    $user = User::factory()->create();
    $certificate = Certificate::factory()->for($user)->approved()->create();

    $remaining = $this->service->remainingCoverageWithout($user, $certificate);

    expect($remaining->every(fn ($r) => ! $r->isCovered()))->toBeTrue();
});

it('resta la copertura generica se un agonistico scade ma un altro resta valido', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::Nuoto, 'entity' => \App\Enums\MembershipEntity::Uisp]);
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);

    $nuoto = Certificate::factory()->for($user)->competitive(Discipline::Nuoto)->approved()->create();
    Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->create();

    $remaining = $this->service->remainingCoverageWithout($user, $nuoto);

    $byDiscipline = $remaining->keyBy(fn ($r) => $r->requirement->isAny() ? 'any' : $r->requirement->discipline->value);
    expect($byDiscipline['nuoto']->isCovered())->toBeFalse();
    expect($byDiscipline['atletica_leggera']->isCovered())->toBeTrue();
    expect($byDiscipline['any']->isCovered())->toBeTrue(); // coperto dall'agonistico atletica residuo
});

// --- Descrizione del requisito scoperto -----------------------------------

it('distingue "mancante" da "scaduto" nella descrizione del requisito scoperto', function () {
    $user = User::factory()->create();
    Membership::factory()->for($user)->create(['is_competitive' => true, 'discipline' => Discipline::AtleticaLeggera, 'entity' => \App\Enums\MembershipEntity::Fidal]);
    Certificate::factory()->for($user)->competitive(Discipline::AtleticaLeggera)->approved()->expired()->create();

    $gaps = $this->service->gapsFor($user);
    $athleticsGap = $gaps->first(fn ($r) => ! $r->requirement->isAny());

    expect($this->service->gapDescription($user, $athleticsGap->requirement))->toContain('scaduto');

    $neverUploaded = User::factory()->create();
    Membership::factory()->for($neverUploaded)->create(['is_competitive' => true, 'discipline' => Discipline::Nuoto, 'entity' => \App\Enums\MembershipEntity::Uisp]);
    $gaps2 = $this->service->gapsFor($neverUploaded);
    $swimGap = $gaps2->first(fn ($r) => ! $r->requirement->isAny());

    expect($this->service->gapDescription($neverUploaded, $swimGap->requirement))->toContain('mancante');
});
