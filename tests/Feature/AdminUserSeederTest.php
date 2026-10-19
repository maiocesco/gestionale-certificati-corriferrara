<?php

use App\Enums\UserRole;
use App\Models\CadastralCode;
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

it('crea l\'amministratore senza dati anagrafici se le variabili ADMIN_* non sono impostate', function () {
    $this->seed(AdminUserSeeder::class);

    $admin = User::where('email', 'maio.cesco@gmail.com')->first();

    expect($admin->birth_date)->toBeNull();
    expect($admin->fiscal_code)->toBeNull();
    expect($admin->birth_cadastral_code_id)->toBeNull();
});

it('completa i dati anagrafici dell\'amministratore se le variabili ADMIN_* sono impostate', function () {
    $ferrara = CadastralCode::factory()->create(['belfiore_code' => 'D548']);

    putenv('ADMIN_BIRTH_DATE=2000-01-01');
    putenv('ADMIN_BIRTH_PLACE_BELFIORE=D548');
    putenv('ADMIN_FISCAL_CODE=TSTTST00A01D548Z');

    try {
        $this->seed(AdminUserSeeder::class);
    } finally {
        putenv('ADMIN_BIRTH_DATE');
        putenv('ADMIN_BIRTH_PLACE_BELFIORE');
        putenv('ADMIN_FISCAL_CODE');
    }

    $admin = User::where('email', 'maio.cesco@gmail.com')->first();

    expect($admin->birth_date->format('Y-m-d'))->toBe('2000-01-01');
    expect($admin->fiscal_code)->toBe('TSTTST00A01D548Z');
    expect($admin->birth_cadastral_code_id)->toBe($ferrara->id);
});
