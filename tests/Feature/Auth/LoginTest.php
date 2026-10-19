<?php

use App\Enums\UserRole;
use App\Models\User;

it('permette il login con credenziali corrette', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard'));
});

it('rifiuta il login con password errata', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'sbagliata',
    ]);

    $response->assertRedirect('/login');
    $this->assertGuest();
});

it('imposta email_verified_at al primo login riuscito', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => bcrypt('password')]);

    expect($user->email_verified_at)->toBeNull();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    expect($user->refresh()->email_verified_at)->not->toBeNull();
});

it('blocca il login di un account disattivato', function () {
    $user = User::factory()->disabled()->create(['password' => bcrypt('password')]);

    $response = $this->from('/login')->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect('/login');
    $this->assertGuest();
});

it('reindirizza al cambio password obbligatorio chi ha must_change_password', function () {
    $user = User::factory()->mustChangePassword()->create(['password' => bcrypt('password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response = $this->get('/dashboard');

    $response->assertRedirect(route('password.change'));
});

it('non espone rotte di registrazione pubblica', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register')->assertNotFound();
});
