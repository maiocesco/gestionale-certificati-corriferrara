<?php

use App\Models\CadastralCode;
use App\Services\FiscalCodeService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new FiscalCodeService();

    $this->ferrara = CadastralCode::factory()->create([
        'name' => 'FERRARA',
        'province' => 'FE',
        'belfiore_code' => 'D548',
    ]);
});

it('valida un codice fiscale coerente', function () {
    $result = $this->service->isConsistent(
        'Mario',
        'Rossi',
        Carbon::parse('1985-03-15'),
        $this->ferrara,
        'RSSMRA85C15D548K',
    );

    expect($result)->toBeTrue();
});

it('è case-insensitive e tollera spazi attorno al codice', function () {
    $result = $this->service->isConsistent(
        'Mario',
        'Rossi',
        Carbon::parse('1985-03-15'),
        $this->ferrara,
        ' rssmra85c15d548k ',
    );

    expect($result)->toBeTrue();
});

it('valida un codice fiscale femminile (giorno +40)', function () {
    $result = $this->service->isConsistent(
        'Maria',
        'Rossi',
        Carbon::parse('1985-03-15'),
        $this->ferrara,
        'RSSMRA85C55D548O',
    );

    expect($result)->toBeTrue();
});

it('valida un codice fiscale con omocodia', function () {
    // Ultima cifra del codice catastale (8) sostituita con la lettera U.
    $omocode = 'RSSMRA85C15D54UH';

    $result = $this->service->isConsistent(
        'Mario',
        'Rossi',
        Carbon::parse('1985-03-15'),
        $this->ferrara,
        $omocode,
    );

    expect($result)->toBeTrue();
});

it('rifiuta un codice con carattere di controllo errato', function () {
    $result = $this->service->isConsistent(
        'Mario',
        'Rossi',
        Carbon::parse('1985-03-15'),
        $this->ferrara,
        'RSSMRA85C15D548X',
    );

    expect($result)->toBeFalse();
});

it('rifiuta un codice con nome o cognome non coerenti', function () {
    $result = $this->service->isConsistent(
        'Luigi',
        'Verdi',
        Carbon::parse('1985-03-15'),
        $this->ferrara,
        'RSSMRA85C15D548K',
    );

    expect($result)->toBeFalse();
});

it('rifiuta un codice con data di nascita non coerente', function () {
    $result = $this->service->isConsistent(
        'Mario',
        'Rossi',
        Carbon::parse('1986-03-15'),
        $this->ferrara,
        'RSSMRA85C15D548K',
    );

    expect($result)->toBeFalse();
});

it('rifiuta un codice con luogo di nascita non coerente', function () {
    $milano = CadastralCode::factory()->create([
        'name' => 'MILANO',
        'province' => 'MI',
        'belfiore_code' => 'F205',
    ]);

    $result = $this->service->isConsistent(
        'Mario',
        'Rossi',
        Carbon::parse('1985-03-15'),
        $milano,
        'RSSMRA85C15D548K',
    );

    expect($result)->toBeFalse();
});

it('rifiuta un codice di lunghezza o formato non valido', function () {
    expect($this->service->isConsistent('Mario', 'Rossi', Carbon::parse('1985-03-15'), $this->ferrara, 'RSSMRA85C15D548'))->toBeFalse();
    expect($this->service->isConsistent('Mario', 'Rossi', Carbon::parse('1985-03-15'), $this->ferrara, ''))->toBeFalse();
});
