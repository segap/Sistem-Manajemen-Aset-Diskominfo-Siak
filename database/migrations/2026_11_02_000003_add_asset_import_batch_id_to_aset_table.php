<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->foreignId('asset_import_batch_id')->nullable()->after('pemegang_aset_id')
                ->constrained('asset_import_batches')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('asset_import_batch_id');
        });
    }
};
