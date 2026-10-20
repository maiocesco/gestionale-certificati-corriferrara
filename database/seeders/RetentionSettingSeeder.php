<?php

namespace Database\Seeders;

use App\Enums\RetentionCategory;
use App\Models\RetentionSetting;
use Illuminate\Database\Seeder;

class RetentionSettingSeeder extends Seeder
{
    /**
     * Precarica i periodi di conservazione di default, se non esistono
     * già: non sovrascrive le modifiche fatte dagli amministratori.
     */
    public function run(): void
    {
        foreach (RetentionCategory::cases() as $category) {
            RetentionSetting::firstOrCreate(
                ['category' => $category->value],
                ['years' => $category->defaultYears()]
            );
        }
    }
}
