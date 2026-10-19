<?php

use App\Models\TrainingLocation;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('impedisce a un non amministratore di gestire le sedi', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.training-locations.index'))->assertForbidden();
});

it('mostra l\'elenco delle sedi', function () {
    TrainingLocation::factory()->create(['name' => 'Runner\'s School Ferrara']);

    $response = $this->actingAs($this->admin)->get(route('admin.training-locations.index'));

    $response->assertOk();
    $response->assertSee('Runner\'s School Ferrara');
});

it('crea una nuova sede', function () {
    $response = $this->actingAs($this->admin)->post(route('admin.training-locations.store'), [
        'name' => 'Runner\'s School Copparo',
        'city' => 'Copparo',
        'address' => 'Via Roma 1',
    ]);

    $response->assertRedirect(route('admin.training-locations.index'));
    expect(TrainingLocation::where('name', 'Runner\'s School Copparo')->exists())->toBeTrue();
});

it('modifica una sede esistente', function () {
    $location = TrainingLocation::factory()->create();

    $response = $this->actingAs($this->admin)->put(route('admin.training-locations.update', $location), [
        'name' => 'Nuovo nome',
        'city' => $location->city,
        'address' => $location->address,
    ]);

    $response->assertRedirect(route('admin.training-locations.index'));
    expect($location->refresh()->name)->toBe('Nuovo nome');
});

it('mostra il numero di atleti nella pagina di conferma eliminazione', function () {
    $location = TrainingLocation::factory()->create();
    User::factory()->count(3)->create(['training_location_id' => $location->id]);

    $response = $this->actingAs($this->admin)->get(route('admin.training-locations.confirm-delete', $location));

    $response->assertOk();
    $response->assertSee('3');
    $response->assertSee(route('admin.users.index', ['training_location_id' => $location->id]), false);
});

it('elimina una sede libera senza atleti assegnati', function () {
    $location = TrainingLocation::factory()->create();

    $response = $this->actingAs($this->admin)->delete(route('admin.training-locations.destroy', $location));

    $response->assertRedirect(route('admin.training-locations.index'));
    expect(TrainingLocation::find($location->id))->toBeNull();
});

it('elimina una sede popolata e lascia gli atleti senza sede', function () {
    $location = TrainingLocation::factory()->create();
    $athletes = User::factory()->count(2)->create(['training_location_id' => $location->id]);

    $this->actingAs($this->admin)->delete(route('admin.training-locations.destroy', $location));

    expect(TrainingLocation::find($location->id))->toBeNull();
    foreach ($athletes as $athlete) {
        expect($athlete->refresh()->training_location_id)->toBeNull();
    }
});
