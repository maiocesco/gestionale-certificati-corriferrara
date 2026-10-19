<?php

use App\Models\CadastralCode;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    CadastralCode::factory()->create(['name' => 'FERRARA', 'province' => 'FE']);
    CadastralCode::factory()->create(['name' => 'FERMO', 'province' => 'FM']);
    CadastralCode::factory()->create(['name' => 'MILANO', 'province' => 'MI']);
});

it('cerca comuni per nome', function () {
    $response = $this->actingAs($this->admin)->getJson(route('admin.cadastral-codes.search', ['q' => 'fer']));

    $response->assertOk();
    $response->assertJsonCount(2);
});

it('non restituisce risultati per ricerche troppo corte', function () {
    $response = $this->actingAs($this->admin)->getJson(route('admin.cadastral-codes.search', ['q' => 'f']));

    $response->assertOk();
    $response->assertJsonCount(0);
});

it('impedisce a un atleta di usare la ricerca', function () {
    $athlete = User::factory()->create();

    $this->actingAs($athlete)->getJson(route('admin.cadastral-codes.search', ['q' => 'fer']))->assertForbidden();
});
