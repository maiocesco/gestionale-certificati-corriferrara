<?php

namespace App\Services\Import;

use Illuminate\Support\Str;

/**
 * Un atleta da importare, ricavato raggruppando le righe del CSV con lo
 * stesso nominativo (al netto di maiuscole/spazi). Più righe = più
 * certificati per lo stesso atleta. Non è un value object immutabile:
 * il parser lo costruisce accumulando righe durante la lettura del CSV.
 */
final class ImportedAthlete
{
    public string $firstName;

    public string $lastName;

    public bool $nameSplitIsAmbiguous = false;

    /** @var list<string> Tutte le varianti grafiche del nominativo incontrate nel CSV (per il report). */
    public array $rawNames = [];

    /** @var list<string> Tutte le "Scuola di appartenenza" non vuote incontrate per questo atleta. */
    public array $trainingLocationNames = [];

    /** @var list<ImportedCertificateRow> */
    public array $certificates = [];

    public function email(string $suffix = ''): string
    {
        $slug = fn (string $s) => Str::slug($s, '');

        return $slug($this->firstName).'.'.$slug($this->lastName).$suffix.'@corriferrara-import.test';
    }
}
