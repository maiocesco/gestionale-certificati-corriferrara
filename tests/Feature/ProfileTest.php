<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('mostra la pagina profilo', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/profile')->assertOk();
});

it('non espone rotte di modifica anagrafica o eliminazione account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile', ['first_name' => 'Altro'])->assertMethodNotAllowed();
    $this->actingAs($user)->delete('/profile')->assertMethodNotAllowed();
});

it('permette di cambiare la propria password dal profilo', function () {
    $user = User::factory()->create(['password' => bcrypt('vecchia-Password1!')]);

    $response = $this->actingAs($user)->put('/password', [
        'current_password' => 'vecchia-Password1!',
        'password' => 'NuovaPassword1!',
        'password_confirmation' => 'NuovaPassword1!',
    ]);

    $response->assertSessionHasNoErrors();
    expect(Hash::check('NuovaPassword1!', $user->refresh()->password))->toBeTrue();
});
