<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('peminjaman', function (Blueprint $table): void {
            if (! Schema::hasColumn('peminjaman', 'surat_peminjaman')) {
                $table->string('surat_peminjaman')->nullable()->after('alasan');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table): void {
            if (Schema::hasColumn('peminjaman', 'surat_peminjaman')) {
                $table->dropColumn('surat_peminjaman');
            }
        });
    }
};
