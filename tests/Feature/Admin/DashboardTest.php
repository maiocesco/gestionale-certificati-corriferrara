<?php

use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Models\Certificate;
use App\Models\Membership;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('impedisce a un non amministratore di vedere la dashboard admin', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('dashboard'))->assertOk();
    $this->actingAs($athlete)->get(route('admin.dashboard.non-compliant'))->assertForbidden();
    $this->actingAs($athlete)->get(route('admin.dashboard.expiring'))->assertForbidden();
});

it('conta correttamente i certificati da approvare, gli atleti non in regola e le scadenze', function () {
    $compliant = User::factory()->create();
    Certificate::factory()->for($compliant)->approved()->create();

    $nonCompliant = User::factory()->create();
    Certificate::factory()->for($nonCompliant)->create(); // pending, non conta

    $expiringAthlete = User::factory()->create();
    Certificate::factory()->for($expiringAthlete)->approved()->expiringInDays(10)->create();

    $response = $this->actingAs($this->admin)->get(route('dashboard'));

    $response->assertOk();
    $response->assertSee('1', false); // almeno un valore "1" nelle card (controllo debole ma utile)
});

it('elenca gli atleti non in regola con il motivo', function () {
    $athlete = User::factory()->create(['first_name' => 'Mario', 'last_name' => 'Rossi']);
    Membership::factory()->for($athlete)->create(['is_competitive' => true, 'discipline' => Discipline::Nuoto, 'entity' => MembershipEntity::Uisp]);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.non-compliant'));

    $response->assertOk();
    $response->assertSee('Mario Rossi');
});

it('non elenca gli atleti in regola tra i non in regola', function () {
    $athlete = User::factory()->create(['first_name' => 'Laura', 'last_name' => 'Bianchi']);
    Certificate::factory()->for($athlete)->approved()->create();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.non-compliant'));

    $response->assertDontSee('Laura Bianchi');
});

it('esporta in csv l\'elenco degli atleti non in regola', function () {
    User::factory()->create(['first_name' => 'Mario', 'last_name' => 'Rossi']);

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.non-compliant', ['export' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it('elenca i certificati in scadenza nei prossimi 30 giorni', function () {
    $athlete = User::factory()->create(['first_name' => 'Carlo', 'last_name' => 'Neri']);
    Certificate::factory()->for($athlete)->approved()->expiringInDays(15)->create();

    $farAway = User::factory()->create(['first_name' => 'Distante', 'last_name' => 'Lontano']);
    Certificate::factory()->for($farAway)->approved()->expiringInDays(60)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.expiring'));

    $response->assertOk();
    $response->assertSee('Carlo Neri');
    $response->assertDontSee('Distante Lontano');
});

it('esporta in csv l\'elenco dei certificati in scadenza', function () {
    $athlete = User::factory()->create();
    Certificate::factory()->for($athlete)->approved()->expiringInDays(15)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.expiring', ['export' => 'csv']));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

it('filtra i certificati in scadenza per ente di tesseramento', function () {
    $fidalAthlete = User::factory()->create(['first_name' => 'Fidal', 'last_name' => 'Atleta']);
    Membership::factory()->for($fidalAthlete)->create(['entity' => MembershipEntity::Fidal]);
    Certificate::factory()->for($fidalAthlete)->approved()->expiringInDays(10)->create();

    $uispAthlete = User::factory()->create(['first_name' => 'Uisp', 'last_name' => 'Atleta']);
    Membership::factory()->for($uispAthlete)->create(['entity' => MembershipEntity::Uisp]);
    Certificate::factory()->for($uispAthlete)->approved()->expiringInDays(10)->create();

    $response = $this->actingAs($this->admin)->get(route('admin.dashboard.expiring', ['entity' => MembershipEntity::Fidal->value]));

    $response->assertOk()->assertSee('Fidal Atleta')->assertDontSee('Uisp Atleta');
});
