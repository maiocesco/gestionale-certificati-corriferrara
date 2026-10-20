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

it('permette a un amministratore di attivare il riepilogo giornaliero', function () {
    $admin = User::factory()->admin()->create(['daily_digest_enabled' => false]);

    $response = $this->actingAs($admin)->put('/profile/digest-preference', ['daily_digest_enabled' => '1']);

    $response->assertRedirect(route('profile.edit'));
    expect($admin->refresh()->daily_digest_enabled)->toBeTrue();
});

it('permette a un amministratore di disattivare il riepilogo giornaliero', function () {
    $admin = User::factory()->admin()->create(['daily_digest_enabled' => true]);

    $this->actingAs($admin)->put('/profile/digest-preference', []);

    expect($admin->refresh()->daily_digest_enabled)->toBeFalse();
});

it('impedisce a un atleta di modificare la preferenza del riepilogo giornaliero', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->put('/profile/digest-preference', ['daily_digest_enabled' => '1'])->assertForbidden();
});
