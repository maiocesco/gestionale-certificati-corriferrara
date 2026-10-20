<?php

use App\Enums\ClubType;
use App\Enums\Discipline;
use App\Enums\MembershipEntity;
use App\Models\ExternalClub;
use App\Models\Membership;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->athlete = User::factory()->create();
});

function validMembershipPayload(array $overrides = []): array
{
    return array_merge([
        'entity' => MembershipEntity::Uisp->value,
        'discipline' => Discipline::AtleticaLeggera->value,
        'club_type' => ClubType::Corriferrara->value,
        'card_number' => 'UISP-001',
        'is_competitive' => '0',
    ], $overrides);
}

it('permette di aggiungere un tesseramento', function () {
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload()
    );

    $response->assertRedirect(route('admin.users.edit', $this->athlete));
    expect($this->athlete->memberships()->count())->toBe(1);
});

it('impedisce a un non amministratore di aggiungere tesseramenti', function () {
    $other = User::factory()->create();

    $this->actingAs($other)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload()
    )->assertForbidden();
});

it('rifiuta due tesseramenti identici (stessa disciplina e stesso ente)', function () {
    Membership::factory()->for($this->athlete)->create([
        'entity' => MembershipEntity::Uisp,
        'discipline' => Discipline::AtleticaLeggera,
    ]);

    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload()
    );

    $response->assertSessionHasErrors('discipline');
    expect($this->athlete->memberships()->count())->toBe(1);
});

it('permette tesseramenti per la stessa disciplina con enti diversi', function () {
    Membership::factory()->for($this->athlete)->create([
        'entity' => MembershipEntity::Uisp,
        'discipline' => Discipline::AtleticaLeggera,
    ]);

    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload(['entity' => MembershipEntity::Csi->value, 'card_number' => 'CSI-001'])
    );

    $response->assertSessionHasNoErrors();
    expect($this->athlete->memberships()->count())->toBe(2);
});

it('rifiuta un abbinamento società/disciplina non ammesso', function () {
    // Corriferrara non copre il ciclismo.
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload(['discipline' => Discipline::Ciclismo->value])
    );

    $response->assertSessionHasErrors('discipline');
    expect($this->athlete->memberships()->count())->toBe(0);
});

it('rifiuta un tesseramento Corriferrara Bike per una disciplina diversa dal ciclismo', function () {
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload(['club_type' => ClubType::CorriferraraBike->value, 'discipline' => Discipline::Nuoto->value])
    );

    $response->assertSessionHasErrors('discipline');
});

it('rifiuta "Altri sport" come disciplina di un tesseramento, non essendo tesserabile', function () {
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload([
            'club_type' => ClubType::External->value,
            'external_club_name' => 'Polisportiva Esempio',
            'discipline' => Discipline::AltriSport->value,
        ])
    );

    $response->assertSessionHasErrors('discipline');
    expect($this->athlete->memberships()->count())->toBe(0);
});

it('rifiuta FIDAL per una disciplina diversa dall\'atletica leggera', function () {
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload(['entity' => MembershipEntity::Fidal->value, 'discipline' => Discipline::Nuoto->value])
    );

    $response->assertSessionHasErrors('discipline');
});

it('impedisce di aggiungere un tesseramento FIDAL senza un certificato agonistico valido per l\'atletica leggera', function () {
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload(['entity' => MembershipEntity::Fidal->value, 'discipline' => Discipline::AtleticaLeggera->value])
    );

    $response->assertSessionHasErrors('discipline');
    expect($this->athlete->memberships()->count())->toBe(0);
});

it('forza sempre agonistico per un tesseramento FIDAL, anche se non richiesto', function () {
    \App\Models\Certificate::factory()->for($this->athlete)->competitive(Discipline::AtleticaLeggera)->approved()->create();

    $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload([
            'entity' => MembershipEntity::Fidal->value,
            'discipline' => Discipline::AtleticaLeggera->value,
            'is_competitive' => '0',
        ])
    );

    $membership = $this->athlete->memberships()->first();
    expect($membership->is_competitive)->toBeTrue();
});

it('crea una nuova società esterna al volo se non esiste già', function () {
    $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload([
            'club_type' => ClubType::External->value,
            'discipline' => Discipline::Ciclismo->value,
            'external_club_name' => 'Polisportiva Putinati',
        ])
    );

    $membership = $this->athlete->memberships()->first();
    expect($membership->externalClub->name)->toBe('Polisportiva Putinati');
    expect(ExternalClub::count())->toBe(1);
});

it('riusa una società esterna esistente se già presente', function () {
    $club = ExternalClub::factory()->create(['name' => 'Polisportiva Putinati']);

    $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload([
            'club_type' => ClubType::External->value,
            'discipline' => Discipline::Ciclismo->value,
            'external_club_id' => $club->id,
        ])
    );

    expect(ExternalClub::count())->toBe(1);
    expect($this->athlete->memberships()->first()->external_club_id)->toBe($club->id);
});

it('richiede una società esterna quando il tipo è "altra società"', function () {
    $response = $this->actingAs($this->admin)->post(
        route('admin.users.memberships.store', $this->athlete),
        validMembershipPayload(['club_type' => ClubType::External->value, 'discipline' => Discipline::Ciclismo->value])
    );

    $response->assertSessionHasErrors('external_club_id');
});

it('permette di modificare un tesseramento esistente', function () {
    $membership = Membership::factory()->for($this->athlete)->create([
        'entity' => MembershipEntity::Uisp,
        'discipline' => Discipline::AtleticaLeggera,
        'card_number' => 'VECCHIO',
    ]);

    $response = $this->actingAs($this->admin)->put(
        route('admin.users.memberships.update', [$this->athlete, $membership]),
        validMembershipPayload(['card_number' => 'NUOVO'])
    );

    $response->assertRedirect(route('admin.users.edit', $this->athlete));
    expect($membership->refresh()->card_number)->toBe('NUOVO');
});

it('permette di eliminare un tesseramento', function () {
    $membership = Membership::factory()->for($this->athlete)->create();

    $response = $this->actingAs($this->admin)->delete(
        route('admin.users.memberships.destroy', [$this->athlete, $membership])
    );

    $response->assertRedirect(route('admin.users.edit', $this->athlete));
    expect(Membership::find($membership->id))->toBeNull();
});
