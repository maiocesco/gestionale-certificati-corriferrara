<?php

use App\Services\TemporaryPasswordGenerator;

it('genera una password di 8 caratteri con tutte le classi di caratteri', function () {
    $generator = new TemporaryPasswordGenerator();

    for ($i = 0; $i < 50; $i++) {
        $password = $generator->generate();

        expect($password)->toHaveLength(8);
        expect($password)->toMatch('/[A-Z]/');
        expect($password)->toMatch('/[a-z]/');
        expect($password)->toMatch('/[0-9]/');
        expect($password)->toMatch('/[^A-Za-z0-9]/');
    }
});

it('genera password diverse a ogni chiamata', function () {
    $generator = new TemporaryPasswordGenerator();

    $passwords = array_map(fn () => $generator->generate(), range(1, 20));

    expect(array_unique($passwords))->toHaveCount(20);
});
