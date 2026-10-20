<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificate_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificate_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('status', 10);
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['certificate_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificate_notifications');
    }
};
