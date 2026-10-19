<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;

it('crea il primo amministratore con i dati previsti', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'maio.cesco@gmail.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin->role)->toBe(UserRole::Admin);
    expect($admin->must_change_password)->toBeTrue();
    expect($admin->is_active)->toBeTrue();
});

it('è idempotente e non duplica l\'amministratore', function () {
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::where('email', 'maio.cesco@gmail.com')->count())->toBe(1);
});
