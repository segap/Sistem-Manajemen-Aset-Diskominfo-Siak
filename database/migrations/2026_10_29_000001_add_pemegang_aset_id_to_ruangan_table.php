<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ruangan', function (Blueprint $table): void {
            $table->foreignId('pemegang_aset_id')
                ->nullable()
                ->after('penanggung_jawab_id')
                ->constrained('pemegang_aset')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ruangan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pemegang_aset_id');
        });
    }
};
