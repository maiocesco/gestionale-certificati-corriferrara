<?php

use App\Enums\ClubType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Models\ExternalClub;
use App\Models\Membership;
use App\Models\TrainingLocation;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('filtra per società', function () {
    $corriferrara = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Corriferrara']);
    Membership::factory()->for($corriferrara)->create(['club_type' => ClubType::Corriferrara]);

    $bike = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Bike']);
    Membership::factory()->for($bike)->create(['club_type' => ClubType::CorriferraraBike, 'discipline' => Discipline::Ciclismo]);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['club_type' => ClubType::Corriferrara->value]));

    $response->assertSee('Atleta Corriferrara');
    $response->assertDontSee('Atleta Bike');
});

it('filtra per ente di tesseramento', function () {
    $fidal = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Fidal']);
    Membership::factory()->fidal()->for($fidal)->create();

    $uisp = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Uisp']);
    Membership::factory()->for($uisp)->create(['entity' => MembershipEntity::Uisp]);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['entity' => MembershipEntity::Fidal->value]));

    $response->assertSee('Atleta Fidal');
    $response->assertDontSee('Atleta Uisp');
});

it('filtra per disciplina', function () {
    $nuoto = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Nuoto']);
    Membership::factory()->for($nuoto)->create(['discipline' => Discipline::Nuoto]);

    $atletica = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Atletica']);
    Membership::factory()->for($atletica)->create(['discipline' => Discipline::AtleticaLeggera]);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['discipline' => Discipline::Nuoto->value]));

    $response->assertSee('Atleta Nuoto');
    $response->assertDontSee('Atleta Atletica');
});

it('filtra per numero di tessera', function () {
    $target = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Target']);
    Membership::factory()->for($target)->create(['card_number' => 'ABC-123']);

    $other = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Altro']);
    Membership::factory()->for($other)->create(['card_number' => 'XYZ-999']);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['card_number' => 'ABC']));

    $response->assertSee('Atleta Target');
    $response->assertDontSee('Atleta Altro');
});

it('filtra per sede', function () {
    $location = TrainingLocation::factory()->create();

    $assigned = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'InSede', 'training_location_id' => $location->id]);
    $unassigned = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'SenzaSede']);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['training_location_id' => $location->id]));

    $response->assertSee('Atleta InSede');
    $response->assertDontSee('Atleta SenzaSede');
});

it('combina più filtri sullo stesso tesseramento', function () {
    $match = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Match']);
    Membership::factory()->for($match)->create([
        'entity' => MembershipEntity::Uisp,
        'discipline' => Discipline::Nuoto,
    ]);

    // Stesso atleta, ma un ente diverso per la stessa disciplina: non deve combaciare con entity=uisp+discipline=atletica.
    $partial = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Parziale']);
    Membership::factory()->for($partial)->create([
        'entity' => MembershipEntity::Uisp,
        'discipline' => Discipline::AtleticaLeggera,
    ]);
    Membership::factory()->for($partial)->create([
        'entity' => MembershipEntity::Csi,
        'discipline' => Discipline::Nuoto,
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', [
        'entity' => MembershipEntity::Uisp->value,
        'discipline' => Discipline::Nuoto->value,
    ]));

    $response->assertSee('Atleta Match');
    $response->assertDontSee('Atleta Parziale');
});

it('filtra per altra società esterna', function () {
    $club = ExternalClub::factory()->create(['name' => 'Polisportiva Putinati']);

    $member = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Esterno']);
    Membership::factory()->for($member)->create([
        'club_type' => ClubType::External,
        'external_club_id' => $club->id,
        'discipline' => Discipline::Ciclismo,
    ]);

    $other = User::factory()->create(['first_name' => 'Atleta', 'last_name' => 'Corriferrara']);
    Membership::factory()->for($other)->create(['club_type' => ClubType::Corriferrara]);

    $response = $this->actingAs($this->admin)->get(route('admin.users.index', ['external_club_id' => $club->id]));

    $response->assertSee('Atleta Esterno');
    $response->assertDontSee('Atleta Corriferrara');
});
