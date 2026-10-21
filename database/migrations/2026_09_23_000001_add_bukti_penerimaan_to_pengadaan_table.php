<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengadaan', function (Blueprint $table): void {
            if (! Schema::hasColumn('pengadaan', 'bukti_penerimaan')) {
                $table->string('bukti_penerimaan')->nullable()->after('catatan_penerimaan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('pengadaan', function (Blueprint $table): void {
            if (Schema::hasColumn('pengadaan', 'bukti_penerimaan')) {
                $table->dropColumn('bukti_penerimaan');
            }
        });
    }
};
