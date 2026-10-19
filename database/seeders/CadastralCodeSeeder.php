<?php

namespace Database\Seeders;

use App\Enums\CadastralCodeType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CadastralCodeSeeder extends Seeder
{
    /**
     * Importa l'elenco ufficiale dei codici catastali (comuni italiani,
     * incluso lo storico dei cambi nome, e stati esteri).
     *
     * Idempotente: se la tabella è già popolata non fa nulla, per non
     * invalidare i riferimenti (birth_cadastral_code_id) già creati.
     */
    public function run(): void
    {
        if (DB::table('cadastral_codes')->count() > 0) {
            $this->command?->info('cadastral_codes già popolata, seeder saltato.');

            return;
        }

        $path = __DIR__.'/data/cadastral_codes.json';

        if (! file_exists($path)) {
            $this->command?->warn("File {$path} non trovato: cadastral_codes non popolata.");

            return;
        }

        $entries = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $now = now();

        collect($entries)
            ->map(function (array $entry) use ($now) {
                $isForeignState = $entry['sigla_provincia'] === 'EE';

                return [
                    'type' => ($isForeignState ? CadastralCodeType::ForeignState : CadastralCodeType::Comune)->value,
                    'province' => $isForeignState ? null : $entry['sigla_provincia'],
                    'name' => $entry['denominazione_ita'],
                    'belfiore_code' => $entry['codice_belfiore'],
                    'valid_from' => $entry['data_inizio_validita'],
                    'valid_until' => $entry['data_fine_validita'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })
            ->chunk(1000)
            ->each(fn ($chunk) => DB::table('cadastral_codes')->insert($chunk->all()));

        $this->command?->info('cadastral_codes popolata con '.count($entries).' voci.');
    }
}
