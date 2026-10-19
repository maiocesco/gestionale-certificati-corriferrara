<?php

namespace App\Services;

use App\Models\CadastralCode;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

/**
 * Calcolo e validazione del codice fiscale italiano, algoritmo ufficiale
 * (incluse le sostituzioni di omocodia).
 *
 * Il sesso non è un campo raccolto dall'anagrafica (non richiesto dalla
 * specifica): la coerenza viene verificata provando entrambe le varianti
 * (maschile/femminile) del giorno di nascita, dato che il codice fiscale
 * stesso lo codifica.
 */
class FiscalCodeService
{
    private const CONSONANTS = 'BCDFGHJKLMNPQRSTVWXYZ';

    private const VOWELS = 'AEIOU';

    private const MONTH_LETTERS = [
        1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'E', 6 => 'H',
        7 => 'L', 8 => 'M', 9 => 'P', 10 => 'R', 11 => 'S', 12 => 'T',
    ];

    /** Posizioni (0-indicizzate, sulle prime 15 cifre) soggette a omocodia. */
    private const OMOCODIA_POSITIONS = [6, 7, 9, 10, 12, 13, 14];

    private const OMOCODIA_LETTER_TO_DIGIT = [
        'L' => '0', 'M' => '1', 'N' => '2', 'P' => '3', 'Q' => '4',
        'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9',
    ];

    private const ODD_POSITION_VALUES = [
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21,
        'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7, 'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21,
        'K' => 2, 'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8, 'S' => 12, 'T' => 14,
        'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23,
    ];

    private const CONTROL_LETTERS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * Verifica che il codice fiscale sia coerente con nome, cognome, data e
     * luogo di nascita (controllo completo, incluso il carattere finale).
     */
    public function isConsistent(
        string $firstName,
        string $lastName,
        CarbonInterface $birthDate,
        CadastralCode $birthPlace,
        string $fiscalCode
    ): bool {
        $fiscalCode = strtoupper(trim($fiscalCode));

        if (! preg_match('/^[A-Z0-9]{16}$/', $fiscalCode)) {
            return false;
        }

        $surnameCode = $this->codeFromSurname($lastName);
        $nameCode = $this->codeFromName($firstName);
        $yearDigits = $birthDate->format('y');
        $monthLetter = self::MONTH_LETTERS[(int) $birthDate->format('n')];
        $belfiore = strtoupper($birthPlace->belfiore_code);

        $denormalized = $this->denormalizeOmocodia($fiscalCode);

        foreach ([0, 40] as $dayOffset) {
            $day = str_pad((string) ((int) $birthDate->format('j') + $dayOffset), 2, '0', STR_PAD_LEFT);
            $expectedBase = $surnameCode.$nameCode.$yearDigits.$monthLetter.$day.$belfiore;

            if (substr($denormalized, 0, 15) === $expectedBase) {
                $expectedControlChar = $this->controlCharacter(substr($fiscalCode, 0, 15));

                if ($expectedControlChar === substr($fiscalCode, 15, 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function denormalizeOmocodia(string $code): string
    {
        $chars = str_split($code);

        foreach (self::OMOCODIA_POSITIONS as $position) {
            $char = $chars[$position] ?? '';

            if (isset(self::OMOCODIA_LETTER_TO_DIGIT[$char])) {
                $chars[$position] = self::OMOCODIA_LETTER_TO_DIGIT[$char];
            }
        }

        return implode('', $chars);
    }

    private function controlCharacter(string $first15): string
    {
        $sum = 0;

        foreach (str_split($first15) as $index => $char) {
            // Posizioni dispari (1-indicizzate: 1,3,5...) = indici pari 0-indicizzati.
            $sum += $index % 2 === 0
                ? self::ODD_POSITION_VALUES[$char]
                : $this->evenPositionValue($char);
        }

        return self::CONTROL_LETTERS[$sum % 26];
    }

    private function evenPositionValue(string $char): int
    {
        if (ctype_digit($char)) {
            return (int) $char;
        }

        return (int) strpos(self::CONTROL_LETTERS, $char);
    }

    private function codeFromSurname(string $surname): string
    {
        return $this->codeFromLetters($this->normalizeLetters($surname));
    }

    private function codeFromName(string $name): string
    {
        $letters = $this->normalizeLetters($name);
        $consonants = $this->consonantsOf($letters);

        if (count($consonants) >= 4) {
            return $consonants[0].$consonants[2].$consonants[3];
        }

        return $this->codeFromLetters($letters);
    }

    private function codeFromLetters(string $letters): string
    {
        $consonants = $this->consonantsOf($letters);
        $vowels = $this->vowelsOf($letters);

        $code = implode('', array_slice($consonants, 0, 3));
        $code .= implode('', array_slice($vowels, 0, 3 - strlen($code)));

        return str_pad($code, 3, 'X');
    }

    private function normalizeLetters(string $value): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper(Str::ascii($value)));
    }

    private function consonantsOf(string $letters): array
    {
        return array_values(array_filter(str_split($letters), fn ($c) => str_contains(self::CONSONANTS, $c)));
    }

    private function vowelsOf(string $letters): array
    {
        return array_values(array_filter(str_split($letters), fn ($c) => str_contains(self::VOWELS, $c)));
    }
}
