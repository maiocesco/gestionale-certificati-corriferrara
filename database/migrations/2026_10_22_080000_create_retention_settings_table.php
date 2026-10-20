<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retention_settings', function (Blueprint $table) {
            $table->id();
            $table->string('category')->unique();
            $table->unsignedSmallInteger('years');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retention_settings');
    }
};
