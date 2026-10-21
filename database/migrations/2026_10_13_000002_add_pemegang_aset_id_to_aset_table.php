<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('aset', 'pemegang_aset_id')) {
            return;
        }

        Schema::table('aset', function (Blueprint $table): void {
            $table->foreignId('pemegang_aset_id')
                ->nullable()
                ->after('pemasok')
                ->constrained('pemegang_aset')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('aset', 'pemegang_aset_id')) {
            return;
        }

        Schema::table('aset', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('pemegang_aset_id');
        });
    }
};
