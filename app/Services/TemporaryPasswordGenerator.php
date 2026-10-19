<?php

namespace App\Services;

class TemporaryPasswordGenerator
{
    private const LENGTH = 8;

    private const UPPER = 'ABCDEFGHJKLMNPQRSTUVWXYZ';

    private const LOWER = 'abcdefghijkmnopqrstuvwxyz';

    private const DIGITS = '23456789';

    private const SYMBOLS = '!@#$%^&*-_+=?';

    /**
     * Genera una password provvisoria di 8 caratteri con almeno una
     * maiuscola, una minuscola, un numero e un simbolo, usando un
     * generatore casuale crittograficamente sicuro.
     */
    public function generate(): string
    {
        $pools = [self::UPPER, self::LOWER, self::DIGITS, self::SYMBOLS];

        $password = array_map(
            fn (string $pool) => $pool[random_int(0, strlen($pool) - 1)],
            $pools,
        );

        $allChars = implode('', $pools);

        while (count($password) < self::LENGTH) {
            $password[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        for ($i = count($password) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$password[$i], $password[$j]] = [$password[$j], $password[$i]];
        }

        return implode('', $password);
    }
}
