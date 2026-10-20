<?php

use App\Models\RetentionSetting;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('impedisce a un non amministratore di accedere ai periodi di conservazione', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->get(route('admin.retention-settings.index'))->assertForbidden();
});

it('mostra le quattro categorie con i valori di default', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.retention-settings.index'))
        ->assertOk()
        ->assertSee('FIDAL')
        ->assertSee('UISP')
        ->assertSee('CSI')
        ->assertSee('Nessun tesseramento');
});

it('aggiorna gli anni di conservazione di una categoria', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.retention-settings.update', 'fidal'), [
        '_category' => 'fidal',
        'years' => 7,
    ]);

    $response->assertRedirect(route('admin.retention-settings.index'));
    expect(RetentionSetting::where('category', 'fidal')->value('years'))->toBe(7);
});

it('rifiuta un valore non valido', function () {
    $response = $this->actingAs($this->admin)->put(route('admin.retention-settings.update', 'fidal'), [
        '_category' => 'fidal',
        'years' => 0,
    ]);

    $response->assertSessionHasErrors('years');
});
