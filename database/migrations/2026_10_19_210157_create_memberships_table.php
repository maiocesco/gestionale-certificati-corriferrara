<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('entity', 20);
            $table->string('discipline', 30);
            $table->string('club_type', 20);
            $table->foreignId('external_club_id')->nullable()->constrained('external_clubs')->nullOnDelete();
            $table->string('card_number');
            $table->boolean('is_competitive');
            $table->timestamps();

            $table->unique(['user_id', 'entity', 'discipline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memberships');
    }
};
