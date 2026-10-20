<?php

namespace App\Console\Commands;

use App\Enums\CertificateStatus;
use App\Enums\UserRole;
use App\Models\Certificate;
use App\Models\TrainingLocation;
use App\Models\User;
use App\Services\CertificateFileStorage;
use App\Services\Import\CertificateCsvParser;
use App\Services\Import\CertificateFileDownloader;
use App\Services\Import\CertificateFileMerger;
use App\Services\Import\ImportedAthlete;
use App\Services\Import\ImportedCertificateRow;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Throwable;

/**
 * Importazione una tantum dei certificati storici da un CSV (export di
 * un Google Form) con i relativi file su Google Drive. Non è una
 * funzionalità permanente del gestionale: vedi CLAUDE.md/SESSION_HANDOFF
 * per il contesto. Ripetibile in sicurezza: salta le righe già importate
 * con successo in un'esecuzione precedente (log di avanzamento) e usa
 * firstOrCreate per utenti e sedi.
 */
class ImportCertificatesFromCsv extends Command
{
    protected $signature = 'import:certificates
        {csv : Percorso del file CSV}
        {--dry-run : Analizza e mostra il riepilogo senza scrivere nulla}
        {--fresh : Ignora il log di avanzamento e riprova anche le righe già fallite o già importate}
        {--log= : Percorso del file di log di avanzamento (default: storage/app/private/import-certificati-log.json)}
        {--report= : Percorso del report finale (default: storage/app/private/import-certificati-report.txt)}';

    protected $description = 'Importa atleti, sedi e certificati da un CSV con i relativi file su Google Drive (importazione una tantum)';

    private array $progress = [];

    private string $logPath;

    public function handle(
        CertificateCsvParser $parser,
        CertificateFileDownloader $downloader,
        CertificateFileMerger $merger,
        CertificateFileStorage $fileStorage,
    ): int {
        $csvPath = $this->argument('csv');

        if (! is_file($csvPath)) {
            $this->error("File non trovato: {$csvPath}");

            return self::FAILURE;
        }

        $admin = User::where('role', UserRole::Admin->value)->orderBy('id')->first();

        if (! $admin) {
            $this->error('Nessun amministratore trovato: serve per registrare chi ha "caricato" i certificati importati.');

            return self::FAILURE;
        }

        $this->logPath = $this->option('log') ?: storage_path('app/private/import-certificati-log.json');
        $this->progress = $this->option('fresh') ? [] : $this->loadProgress();

        $athletes = $parser->parse($csvPath);

        $totalCertificates = $athletes->sum(fn (ImportedAthlete $a) => count($a->certificates));
        $this->info("Nominativi trovati: {$athletes->count()} — certificati da importare: {$totalCertificates}.");

        $ambiguousNames = $athletes->filter(fn (ImportedAthlete $a) => $a->nameSplitIsAmbiguous)->values();
        $this->info("Nominativi con divisione cognome/nome ambigua: {$ambiguousNames->count()}.");

        $locationNames = $athletes->flatMap(fn (ImportedAthlete $a) => $a->trainingLocationNames)->unique()->sort()->values();
        $this->info('Sedi Runner\'s School da creare/riusare: '.$locationNames->implode(', '));

        if ($this->option('dry-run')) {
            $this->info('Dry run: nessuna scrittura effettuata.');

            return self::SUCCESS;
        }

        $locations = $this->ensureTrainingLocations($locationNames);

        $stats = ['users_created' => 0, 'users_reused' => 0, 'certificates_created' => 0, 'certificates_skipped' => 0, 'certificates_failed' => 0];
        $failures = [];

        $bar = $this->output->createProgressBar($totalCertificates);
        $bar->start();

        foreach ($athletes as $athlete) {
            try {
                $user = $this->ensureAthlete($athlete, $locations, $stats);
            } catch (Throwable $e) {
                $bar->advance(count($athlete->certificates));
                $failures[] = "atleta {$athlete->firstName} {$athlete->lastName} ({$athlete->email()}): impossibile creare/trovare l'utente — {$e->getMessage()}";
                $stats['certificates_failed'] += count($athlete->certificates);

                continue;
            }

            foreach ($athlete->certificates as $row) {
                $bar->advance();
                // Una riga CSV con più tipologie genera più ImportedCertificateRow
                // con lo stesso csvLineNumber: tipo+disciplina le distingue nel log.
                $progressKey = $row->csvLineNumber.':'.$row->type->value.':'.($row->discipline?->value ?? '');

                if (! $this->option('fresh') && ($this->progress[$progressKey]['status'] ?? null) === 'success') {
                    $stats['certificates_skipped']++;

                    continue;
                }

                try {
                    $this->importCertificate($row, $user, $admin, $downloader, $merger, $fileStorage);
                    $this->progress[$progressKey] = ['status' => 'success', 'athlete' => $athlete->email()];
                    $stats['certificates_created']++;
                } catch (Throwable $e) {
                    $this->progress[$progressKey] = ['status' => 'failed', 'athlete' => $athlete->email(), 'reason' => $e->getMessage()];
                    $failures[] = "riga {$row->csvLineNumber} ({$athlete->firstName} {$athlete->lastName}): {$e->getMessage()}";
                    $stats['certificates_failed']++;
                }

                $this->saveProgress();
            }
        }

        $bar->finish();
        $this->newLine(2);

        $this->writeReport($stats, $ambiguousNames, $failures);

        $this->info("Atleti creati: {$stats['users_created']}, riusati: {$stats['users_reused']}.");
        $this->info("Certificati importati: {$stats['certificates_created']}, saltati (già fatti): {$stats['certificates_skipped']}, falliti: {$stats['certificates_failed']}.");

        return self::SUCCESS;
    }

    /**
     * @return array<string, TrainingLocation>
     */
    private function ensureTrainingLocations($locationNames): array
    {
        $locations = [];

        foreach ($locationNames as $name) {
            $locations[$name] = TrainingLocation::firstOrCreate(['name' => $name], ['city' => $name]);
        }

        return $locations;
    }

    /**
     * Crea l'atleta o riusa quello esistente con la stessa email. Oltre
     * al controllo preventivo, il ciclo gestisce anche il raro caso in
     * cui l'INSERT fallisca comunque per email duplicata (visto durante
     * l'importazione reale, causa non isolata con certezza): in quel
     * caso riprova con un suffisso diverso invece di far fallire tutta
     * l'importazione per un solo atleta.
     */
    private function ensureAthlete(ImportedAthlete $athlete, array $locations, array &$stats): User
    {
        $locationName = $athlete->trainingLocationNames[0] ?? null;

        for ($suffix = 1; $suffix <= 10; $suffix++) {
            $email = $suffix === 1 ? $athlete->email() : $athlete->email('-'.$suffix);

            $existing = User::where('email', $email)->first();

            if ($existing) {
                if ($this->emailBelongsToThisAthlete($email, $athlete)) {
                    $stats['users_reused']++;

                    return $existing;
                }

                continue;
            }

            try {
                $user = User::create([
                    'first_name' => $athlete->firstName,
                    'last_name' => $athlete->lastName,
                    'email' => $email,
                    'password' => Str::random(40),
                    'role' => UserRole::Athlete,
                    'is_active' => true,
                    'must_change_password' => true,
                    'training_location_id' => $locationName ? $locations[$locationName]->id : null,
                ]);

                $stats['users_created']++;

                return $user;
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                continue; // un'altra riga ha appena preso questa email: riprova con il suffisso successivo
            }
        }

        throw new \RuntimeException("Impossibile creare un'email univoca per {$athlete->firstName} {$athlete->lastName} dopo 10 tentativi.");
    }

    /**
     * Un'email generata può già esistere da un'esecuzione precedente
     * dello stesso import per lo stesso atleta: in quel caso va riusata,
     * non trattata come collisione con un'altra persona.
     */
    private function emailBelongsToThisAthlete(string $email, ImportedAthlete $athlete): bool
    {
        $user = User::where('email', $email)->first();

        return $user && $user->first_name === $athlete->firstName && $user->last_name === $athlete->lastName;
    }

    private function importCertificate(
        ImportedCertificateRow $row,
        User $athlete,
        User $admin,
        CertificateFileDownloader $downloader,
        CertificateFileMerger $merger,
        CertificateFileStorage $fileStorage,
    ): void {
        $downloaded = array_map(fn (string $link) => $downloader->download($link), $row->driveLinks);

        try {
            $merged = $merger->merge($downloaded);

            $uploadedFile = new UploadedFile(
                $merged->path,
                "certificato.{$merged->extension}",
                $merged->mimeType,
                null,
                true,
            );

            $fileData = $fileStorage->store($uploadedFile, $athlete);

            $certificate = Certificate::create([
                ...$fileData,
                'user_id' => $athlete->id,
                'uploaded_by' => $admin->id,
                'type' => $row->type,
                'discipline' => $row->discipline,
                'expires_at' => $row->expiresAt,
                'status' => CertificateStatus::Approved,
                'reviewed_by' => $admin->id,
                'reviewed_at' => $row->submittedAt,
            ]);

            $certificate->forceFill(['created_at' => $row->submittedAt, 'updated_at' => $row->submittedAt])->saveQuietly();
        } finally {
            foreach ($downloaded as $file) {
                @unlink($file->path);
            }
            if (isset($merged) && ! in_array($merged, $downloaded, true)) {
                @unlink($merged->path);
            }
        }
    }

    private function loadProgress(): array
    {
        if (! is_file($this->logPath)) {
            return [];
        }

        return json_decode(file_get_contents($this->logPath), true) ?? [];
    }

    private function saveProgress(): void
    {
        if (! is_dir(dirname($this->logPath))) {
            mkdir(dirname($this->logPath), 0755, true);
        }

        file_put_contents($this->logPath, json_encode($this->progress, JSON_PRETTY_PRINT));
    }

    private function writeReport(array $stats, $ambiguousNames, array $failures): void
    {
        $reportPath = $this->option('report') ?: storage_path('app/private/import-certificati-report.txt');

        $lines = [
            'Report importazione certificati — '.now()->format('d/m/Y H:i'),
            '',
            "Atleti creati: {$stats['users_created']}, riusati: {$stats['users_reused']}.",
            "Certificati importati: {$stats['certificates_created']}, saltati: {$stats['certificates_skipped']}, falliti: {$stats['certificates_failed']}.",
            '',
            '--- Nominativi con divisione cognome/nome da verificare manualmente ('.$ambiguousNames->count().') ---',
        ];

        foreach ($ambiguousNames as $athlete) {
            $lines[] = "- {$athlete->firstName} {$athlete->lastName}  (scritto originariamente: ".implode(' | ', $athlete->rawNames).')';
        }

        $lines[] = '';
        $lines[] = '--- Certificati non importati ('.count($failures).') ---';
        foreach ($failures as $failure) {
            $lines[] = "- {$failure}";
        }

        if (! is_dir(dirname($reportPath))) {
            mkdir(dirname($reportPath), 0755, true);
        }

        file_put_contents($reportPath, implode(PHP_EOL, $lines));
        $this->info("Report completo salvato in: {$reportPath}");
    }
}
