<?php

namespace App\Services\Import;

use App\Enums\CertificateType;
use App\Enums\Discipline;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Legge il CSV "certificati-medici.csv" (export di un Google Form) e lo
 * trasforma in un elenco di ImportedAthlete, ciascuno con i certificati
 * da creare. Pura trasformazione dati: nessun accesso a rete o database,
 * per poterla testare in isolamento.
 *
 * Regole di importazione concordate con il cliente:
 * - stesso nominativo (a meno di maiuscole/spazi) = stesso atleta, più
 *   certificati;
 * - una riga con più tipologie elencate (es. "AGONISTICO PER ATLETICA
 *   LEGGERA, AGONISTICO PER CICLISMO") genera un certificato per
 *   tipologia, con lo stesso file;
 * - "Scuola di appartenenza" diventa la sede Runner's School
 *   dell'atleta, tranne le voci non geografiche (vedi
 *   NO_LOCATION_SCHOOLS) per cui l'atleta resta senza sede;
 * - cognome e nome sono divisi con l'euristica "ultima parola = nome,
 *   il resto = cognome"; i nominativi di 3+ parole sono segnalati come
 *   ambigui per una verifica manuale successiva.
 */
class CertificateCsvParser
{
    /**
     * Voci di "Scuola di appartenenza" che non sono sedi Runner's School
     * reali (gruppi/progetti o "nessuna sede"): l'atleta resta senza
     * sede assegnata.
     */
    private const NO_LOCATION_SCHOOLS = [
        "tesserato corriferrara (no runner's school)",
        'tesserato corriferrara',
        'trail section',
        'acqua',
        'camp',
        'progetto ferrara marathon',
    ];

    /**
     * @var array<string, array{0: CertificateType, 1: ?Discipline}>
     */
    private const TYPE_MAP = [
        'non agonistico' => [CertificateType::NonCompetitive, null],
        'agonistico per atletica leggera' => [CertificateType::Competitive, Discipline::AtleticaLeggera],
        'agonistico per ciclismo' => [CertificateType::Competitive, Discipline::Ciclismo],
        'agonistico per nuoto' => [CertificateType::Competitive, Discipline::Nuoto],
        'agonistico per altri sport' => [CertificateType::Competitive, Discipline::AltriSport],
    ];

    /**
     * @return Collection<int, ImportedAthlete>
     */
    public function parse(string $csvPath): Collection
    {
        $handle = fopen($csvPath, 'r');

        if ($handle === false) {
            throw new RuntimeException("Impossibile aprire il file CSV: {$csvPath}");
        }

        fgetcsv($handle, null, ',', '"', ''); // intestazione, non serve

        /** @var array<string, ImportedAthlete> $athletes */
        $athletes = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $lineNumber++;

            if (count($row) < 6 || trim($row[1] ?? '') === '') {
                continue;
            }

            [$timestampRaw, $rawName, $school, $linksRaw, $expiresRaw, $typesRaw] = $row;

            $rawName = trim($rawName);
            $normalizedName = $this->normalizeName($rawName);

            if (! isset($athletes[$normalizedName])) {
                $athletes[$normalizedName] = new ImportedAthlete();
                [$athletes[$normalizedName]->firstName, $athletes[$normalizedName]->lastName, $athletes[$normalizedName]->nameSplitIsAmbiguous] = $this->splitName($rawName);
            }

            $athlete = $athletes[$normalizedName];

            if (! in_array($rawName, $athlete->rawNames, true)) {
                $athlete->rawNames[] = $rawName;
            }

            $locationName = $this->trainingLocationName($school);
            if ($locationName !== null && ! in_array($locationName, $athlete->trainingLocationNames, true)) {
                $athlete->trainingLocationNames[] = $locationName;
            }

            $links = $this->parseLinks($linksRaw);
            $expiresAt = $this->parseDate($expiresRaw);
            $submittedAt = $this->parseTimestamp($timestampRaw);

            if ($links === [] || $expiresAt === null) {
                continue;
            }

            foreach ($this->parseTypes($typesRaw) as [$type, $discipline]) {
                $athlete->certificates[] = new ImportedCertificateRow(
                    csvLineNumber: $lineNumber,
                    type: $type,
                    discipline: $discipline,
                    expiresAt: $expiresAt,
                    driveLinks: $links,
                    submittedAt: $submittedAt ?? $expiresAt,
                );
            }
        }

        fclose($handle);

        return collect(array_values($athletes));
    }

    private function normalizeName(string $rawName): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($rawName)));
    }

    /**
     * @return array{0: string, 1: string, 2: bool} [nome, cognome, ambiguo]
     */
    private function splitName(string $rawName): array
    {
        $collapsed = preg_replace('/\s+/u', ' ', trim($rawName));
        $words = explode(' ', $collapsed);

        $firstName = array_pop($words);
        $lastName = implode(' ', $words);

        $firstName = $this->titleCase($firstName);
        $lastName = $this->titleCase($lastName);

        return [$firstName, $lastName, count($words) >= 2];
    }

    private function titleCase(string $value): string
    {
        return mb_convert_case(trim($value), MB_CASE_TITLE, 'UTF-8');
    }

    private function trainingLocationName(string $school): ?string
    {
        $school = trim($school);

        if ($school === '') {
            return null;
        }

        if (in_array(mb_strtolower($school), self::NO_LOCATION_SCHOOLS, true)) {
            return null;
        }

        return $this->titleCase($school);
    }

    /**
     * @return list<string>
     */
    private function parseLinks(string $linksRaw): array
    {
        return collect(explode(',', $linksRaw))
            ->map(fn (string $link) => trim($link))
            ->filter(fn (string $link) => $link !== '')
            ->values()
            ->all();
    }

    private function parseDate(string $dateRaw): ?CarbonImmutable
    {
        $dateRaw = trim($dateRaw);

        if ($dateRaw === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('d/m/Y', $dateRaw)?->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseTimestamp(string $timestampRaw): ?CarbonImmutable
    {
        $timestampRaw = trim($timestampRaw);

        if ($timestampRaw === '') {
            return null;
        }

        try {
            return CarbonImmutable::createFromFormat('d/m/Y H.i.s', $timestampRaw) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<array{0: CertificateType, 1: ?Discipline}>
     */
    private function parseTypes(string $typesRaw): array
    {
        $types = [];

        foreach (explode(',', $typesRaw) as $typeRaw) {
            $key = mb_strtolower(trim($typeRaw));

            if (isset(self::TYPE_MAP[$key]) && ! in_array(self::TYPE_MAP[$key], $types, true)) {
                $types[] = self::TYPE_MAP[$key];
            }
        }

        return $types;
    }
}
