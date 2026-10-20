<?php

use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Services\Import\CertificateCsvParser;

function writeImportCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'import-csv-test-');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Informazioni cronologiche', 'COGNOME E NOME', 'Scuola di appartenenza', '', 'DATA DI SCADENZA', 'TIPOLOGIA CERTIFCIATO']);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

it('divide cognome e nome usando l\'ultima parola come nome', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Cestari Lorenzo', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    $athletes = (new CertificateCsvParser())->parse($csv);

    expect($athletes)->toHaveCount(1);
    expect($athletes->first()->firstName)->toBe('Lorenzo');
    expect($athletes->first()->lastName)->toBe('Cestari');
    expect($athletes->first()->nameSplitIsAmbiguous)->toBeFalse();
});

it('segnala come ambiguo un nominativo di 3 o più parole', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'De Gennaro Elisa Francesca', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    $athletes = (new CertificateCsvParser())->parse($csv);

    expect($athletes->first()->nameSplitIsAmbiguous)->toBeTrue();
    expect($athletes->first()->firstName)->toBe('Francesca');
    expect($athletes->first()->lastName)->toBe('De Gennaro Elisa');
});

it('raggruppa lo stesso nominativo scritto con maiuscole o spazi diversi come un solo atleta', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'ZOLI NICOLA', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
        ['19/10/2026 10.00.00', 'Zoli Nicola ', 'Ferrara', 'https://drive.google.com/open?id=BBB', '12/12/2027', 'AGONISTICO PER CICLISMO'],
    ]);

    $athletes = (new CertificateCsvParser())->parse($csv);

    expect($athletes)->toHaveCount(1);
    expect($athletes->first()->certificates)->toHaveCount(2);
    expect($athletes->first()->rawNames)->toBe(['ZOLI NICOLA', 'Zoli Nicola']);
});

it('genera un certificato per ciascuna tipologia elencata nella stessa riga', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'AGONISTICO PER ATLETICA LEGGERA, AGONISTICO PER CICLISMO'],
    ]);

    $athletes = (new CertificateCsvParser())->parse($csv);
    $certificates = $athletes->first()->certificates;

    expect($certificates)->toHaveCount(2);
    expect($certificates[0]->discipline)->toBe(Discipline::AtleticaLeggera);
    expect($certificates[1]->discipline)->toBe(Discipline::Ciclismo);
    expect($certificates[0]->driveLinks)->toBe(['https://drive.google.com/open?id=AAA']);
    expect($certificates[1]->driveLinks)->toBe(['https://drive.google.com/open?id=AAA']);
});

it('mappa "agonistico per altri sport" sulla disciplina Altri sport', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'AGONISTICO PER ALTRI SPORT'],
    ]);

    $certificate = (new CertificateCsvParser())->parse($csv)->first()->certificates[0];

    expect($certificate->type)->toBe(CertificateType::Competitive);
    expect($certificate->discipline)->toBe(Discipline::AltriSport);
});

it('divide più link allo stesso certificato in una lista', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA, https://drive.google.com/open?id=BBB', '12/12/2026', 'NON AGONISTICO'],
    ]);

    $certificate = (new CertificateCsvParser())->parse($csv)->first()->certificates[0];

    expect($certificate->driveLinks)->toBe(['https://drive.google.com/open?id=AAA', 'https://drive.google.com/open?id=BBB']);
});

it('assegna la sede Runner\'s School corrispondente alla scuola di appartenenza', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Cento', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    expect((new CertificateCsvParser())->parse($csv)->first()->trainingLocationNames)->toBe(['Cento']);
});

it('non assegna alcuna sede per le voci non geografiche o "no Runner\'s School"', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', "TESSERATO CORRIFERRARA (no Runner's School)", 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
        ['18/10/2026 10.00.00', 'Bianchi Carla', 'Trail Section', 'https://drive.google.com/open?id=BBB', '12/12/2026', 'NON AGONISTICO'],
    ]);

    $athletes = (new CertificateCsvParser())->parse($csv);

    expect($athletes->first(fn ($a) => $a->lastName === 'Rossi')->trainingLocationNames)->toBe([]);
    expect($athletes->first(fn ($a) => $a->lastName === 'Bianchi')->trainingLocationNames)->toBe([]);
});

it('importa anche i certificati con scadenza già passata', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '01/01/2020', 'NON AGONISTICO'],
    ]);

    $certificate = (new CertificateCsvParser())->parse($csv)->first()->certificates[0];

    expect($certificate->expiresAt->format('Y-m-d'))->toBe('2020-01-01');
});

it('salta le righe senza alcun link', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', '', '12/12/2026', 'NON AGONISTICO'],
    ]);

    expect((new CertificateCsvParser())->parse($csv)->first()->certificates)->toBe([]);
});

it('genera un\'email segnaposto univoca nel formato nome.cognome', function () {
    $csv = writeImportCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    $athlete = (new CertificateCsvParser())->parse($csv)->first();

    expect($athlete->email())->toBe('mario.rossi@corriferrara-import.test');
});
