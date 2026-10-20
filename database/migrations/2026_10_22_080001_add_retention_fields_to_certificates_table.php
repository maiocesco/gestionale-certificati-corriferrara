<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('file_path')->nullable()->change();
            $table->string('file_original_name')->nullable()->change();
            $table->string('file_mime', 100)->nullable()->change();
            $table->unsignedBigInteger('file_size')->nullable()->change();

            $table->timestamp('retention_notified_at')->nullable()->after('reviewed_at');
            $table->timestamp('deleted_at')->nullable()->after('retention_notified_at');
            $table->foreignId('deleted_by')->nullable()->after('deleted_at')->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropConstrainedForeignId('deleted_by');
            $table->dropColumn(['retention_notified_at', 'deleted_at']);

            $table->string('file_path')->nullable(false)->change();
            $table->string('file_original_name')->nullable(false)->change();
            $table->string('file_mime', 100)->nullable(false)->change();
            $table->unsignedBigInteger('file_size')->nullable(false)->change();
        });
    }
};
