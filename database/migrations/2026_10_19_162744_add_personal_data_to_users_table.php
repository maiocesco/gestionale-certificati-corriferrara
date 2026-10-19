<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('birth_date')->after('last_name')->nullable();
            $table->foreignId('birth_cadastral_code_id')->after('birth_date')->nullable()
                ->constrained('cadastral_codes')->nullOnDelete();
            $table->string('fiscal_code', 16)->after('birth_cadastral_code_id')->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('birth_cadastral_code_id');
            $table->dropColumn(['birth_date', 'fiscal_code']);
        });
    }
};
