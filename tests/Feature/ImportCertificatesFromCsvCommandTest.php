<?php

use App\Enums\CertificateStatus;
use App\Enums\CertificateType;
use App\Enums\Discipline;
use App\Models\Certificate;
use App\Models\TrainingLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function writeCommandTestCsv(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'import-command-test-');
    $handle = fopen($path, 'w');
    fputcsv($handle, ['Informazioni cronologiche', 'COGNOME E NOME', 'Scuola di appartenenza', '', 'DATA DI SCADENZA', 'TIPOLOGIA CERTIFCIATO']);
    foreach ($rows as $row) {
        fputcsv($handle, $row);
    }
    fclose($handle);

    return $path;
}

/**
 * Il log/report dell'import vive su disco reale (serve a sopravvivere a
 * un'interruzione), quindi non viene isolato da RefreshDatabase: ogni
 * test deve avere il proprio percorso per non "vedere" righe già
 * importate da un test precedente.
 */
function runImportCommand(\Tests\TestCase $test, string $csv, array $extraOptions = [])
{
    $logPath = sys_get_temp_dir().'/import-test-log-'.uniqid().'.json';
    $reportPath = sys_get_temp_dir().'/import-test-report-'.uniqid().'.txt';

    return $test->artisan('import:certificates', [
        'csv' => $csv,
        '--log' => $logPath,
        '--report' => $reportPath,
        ...$extraOptions,
    ]);
}

/**
 * Http::fake() valuta i pattern nell'ordine in cui sono stati registrati
 * e si ferma al primo che corrisponde: un fake "generico" registrato nel
 * beforeEach intercetterebbe anche gli URL che un singolo test vuole far
 * fallire, registrato dopo. Ogni test richiama quindi fakeDownloads() da
 * solo, con l'elenco completo dei pattern che gli servono.
 */
function fakeDownloads(array $extraPatterns = []): void
{
    Http::fake([
        ...$extraPatterns,
        'drive.google.com/uc*' => Http::response('%PDF-1.4 contenuto finto', 200, ['Content-Type' => 'application/pdf']),
    ]);
}

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
});

it('importa atleti, sedi e certificati dal csv', function () {
    fakeDownloads();

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
        ['18/10/2026 10.00.00', 'Bianchi Carla', 'Cento', 'https://drive.google.com/open?id=BBB', '12/12/2026', 'AGONISTICO PER ATLETICA LEGGERA, AGONISTICO PER CICLISMO'],
    ]);

    runImportCommand($this, $csv)->assertSuccessful();

    expect(User::where('email', 'mario.rossi@corriferrara-import.test')->exists())->toBeTrue();
    $carla = User::where('email', 'carla.bianchi@corriferrara-import.test')->first();
    expect($carla)->not->toBeNull();
    expect($carla->trainingLocation->name)->toBe('Cento');
    expect(TrainingLocation::where('name', 'Ferrara')->exists())->toBeTrue();

    expect(Certificate::where('user_id', $carla->id)->count())->toBe(2);
    $certificate = Certificate::where('user_id', $carla->id)->first();
    expect($certificate->status)->toBe(CertificateStatus::Approved);
    expect($certificate->uploaded_by)->toBe($this->admin->id);
    expect($certificate->reviewed_by)->toBe($this->admin->id);
    expect($certificate->file_path)->not->toBeNull();
});

it('crea un solo certificato per riga con una sola tipologia', function () {
    fakeDownloads();

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    runImportCommand($this, $csv)->assertSuccessful();

    $user = User::where('email', 'mario.rossi@corriferrara-import.test')->first();
    expect(Certificate::where('user_id', $user->id)->count())->toBe(1);
    expect(Certificate::where('user_id', $user->id)->first()->type)->toBe(CertificateType::NonCompetitive);
});

it('unisce in un solo atleta più righe con lo stesso nominativo', function () {
    fakeDownloads();

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'ROSSI MARIO', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
        ['19/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=BBB', '12/12/2027', 'AGONISTICO PER CICLISMO'],
    ]);

    runImportCommand($this, $csv)->assertSuccessful();

    expect(User::where('email', 'mario.rossi@corriferrara-import.test')->count())->toBe(1);
    $user = User::where('email', 'mario.rossi@corriferrara-import.test')->first();
    expect(Certificate::where('user_id', $user->id)->count())->toBe(2);
});

it('non assegna nessuna sede per gli atleti "no Runner\'s School"', function () {
    fakeDownloads();

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', "TESSERATO CORRIFERRARA (no Runner's School)", 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    runImportCommand($this, $csv)->assertSuccessful();

    $user = User::where('email', 'mario.rossi@corriferrara-import.test')->first();
    expect($user->training_location_id)->toBeNull();
});

it('mappa "agonistico per altri sport" sulla disciplina dedicata', function () {
    fakeDownloads();

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'AGONISTICO PER ALTRI SPORT'],
    ]);

    runImportCommand($this, $csv)->assertSuccessful();

    $user = User::where('email', 'mario.rossi@corriferrara-import.test')->first();
    $certificate = Certificate::where('user_id', $user->id)->first();
    expect($certificate->discipline)->toBe(Discipline::AltriSport);
});

it('è ripetibile: una seconda esecuzione non duplica atleti né certificati già importati', function () {
    fakeDownloads();

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=AAA', '12/12/2026', 'NON AGONISTICO'],
    ]);

    $logPath = sys_get_temp_dir().'/import-test-log-'.uniqid().'.json';

    $this->artisan('import:certificates', ['csv' => $csv, '--log' => $logPath])->assertSuccessful();
    $this->artisan('import:certificates', ['csv' => $csv, '--log' => $logPath])->assertSuccessful();

    expect(User::where('email', 'mario.rossi@corriferrara-import.test')->count())->toBe(1);
    $user = User::where('email', 'mario.rossi@corriferrara-import.test')->first();
    expect(Certificate::where('user_id', $user->id)->count())->toBe(1);

    @unlink($logPath);
});

it('continua con le altre righe se il download di una fallisce', function () {
    fakeDownloads([
        'drive.google.com/uc*id=FAIL*' => Http::response('<html>login</html>', 200, ['Content-Type' => 'text/html']),
    ]);

    $csv = writeCommandTestCsv([
        ['18/10/2026 10.00.00', 'Rossi Mario', 'Ferrara', 'https://drive.google.com/open?id=FAIL', '12/12/2026', 'NON AGONISTICO'],
        ['18/10/2026 10.00.00', 'Bianchi Carla', 'Ferrara', 'https://drive.google.com/open?id=OK', '12/12/2026', 'NON AGONISTICO'],
    ]);

    runImportCommand($this, $csv)->assertSuccessful();

    expect(Certificate::count())->toBe(1);
    expect(User::where('email', 'mario.rossi@corriferrara-import.test')->exists())->toBeTrue(); // l'atleta viene comunque creato
    expect(Certificate::whereHas('user', fn ($q) => $q->where('email', 'carla.bianchi@corriferrara-import.test'))->exists())->toBeTrue();
});
