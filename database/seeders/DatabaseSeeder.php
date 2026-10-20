<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CadastralCodeSeeder::class,
            AdminUserSeeder::class,
            EmailAccountSeeder::class,
            EmailTemplateSeeder::class,
            RetentionSettingSeeder::class,
        ]);
    }
}
