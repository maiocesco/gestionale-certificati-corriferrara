<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('permette di impostare una nuova password valida', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => bcrypt('Provv1sori@')]);

    $response = $this->actingAs($user)->put('/cambia-password', [
        'password' => 'NuovaPassw0rd!',
        'password_confirmation' => 'NuovaPassw0rd!',
    ]);

    $response->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->must_change_password)->toBeFalse();
    expect(Hash::check('NuovaPassw0rd!', $user->password))->toBeTrue();
});

it('rifiuta la nuova password se identica a quella provvisoria', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => bcrypt('Provv1sori@')]);

    $response = $this->actingAs($user)->put('/cambia-password', [
        'password' => 'Provv1sori@',
        'password_confirmation' => 'Provv1sori@',
    ]);

    $response->assertSessionHasErrors('password');
    expect($user->refresh()->must_change_password)->toBeTrue();
});

it('rifiuta una password che non rispetta i requisiti minimi', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => bcrypt('Provv1sori@')]);

    $response = $this->actingAs($user)->put('/cambia-password', [
        'password' => 'semplice',
        'password_confirmation' => 'semplice',
    ]);

    $response->assertSessionHasErrors('password');
});

it('blocca ogni altra pagina finché la password non viene cambiata', function () {
    $user = User::factory()->mustChangePassword()->create();

    $this->actingAs($user)->get('/profile')->assertRedirect(route('password.change'));
});

it('non blocca più le altre pagine dopo il cambio password', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => bcrypt('Provv1sori@')]);

    $this->actingAs($user)->put('/cambia-password', [
        'password' => 'NuovaPassw0rd!',
        'password_confirmation' => 'NuovaPassw0rd!',
    ]);

    $this->get('/profile')->assertOk();
});
