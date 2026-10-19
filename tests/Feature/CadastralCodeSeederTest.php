<?php

use App\Enums\CadastralCodeType;
use App\Models\CadastralCode;
use Database\Seeders\CadastralCodeSeeder;

it('importa comuni e stati esteri dal file ufficiale', function () {
    $this->seed(CadastralCodeSeeder::class);

    expect(CadastralCode::count())->toBeGreaterThan(16000);

    $ferrara = CadastralCode::where('name', 'FERRARA')->where('province', 'FE')->first();
    expect($ferrara)->not->toBeNull();
    expect($ferrara->belfiore_code)->toBe('D548');
    expect($ferrara->type)->toBe(CadastralCodeType::Comune);

    $afghanistan = CadastralCode::where('name', 'AFGHANISTAN')->first();
    expect($afghanistan)->not->toBeNull();
    expect($afghanistan->type)->toBe(CadastralCodeType::ForeignState);
    expect($afghanistan->province)->toBeNull();
});

it('è idempotente: non duplica i dati se già popolata', function () {
    $this->seed(CadastralCodeSeeder::class);
    $countAfterFirst = CadastralCode::count();

    $this->seed(CadastralCodeSeeder::class);

    expect(CadastralCode::count())->toBe($countAfterFirst);
});
