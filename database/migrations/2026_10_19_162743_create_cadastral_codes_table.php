<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadastral_codes', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('province', 2)->nullable();
            $table->string('name');
            $table->string('belfiore_code', 4);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index('belfiore_code');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadastral_codes');
    }
};
