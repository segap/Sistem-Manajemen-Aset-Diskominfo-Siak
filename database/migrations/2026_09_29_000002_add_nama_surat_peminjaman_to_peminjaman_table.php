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
            if (! Schema::hasColumn('peminjaman', 'nama_surat_peminjaman')) {
                $table->string('nama_surat_peminjaman')->nullable()->after('surat_peminjaman');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('peminjaman', function (Blueprint $table): void {
            if (Schema::hasColumn('peminjaman', 'nama_surat_peminjaman')) {
                $table->dropColumn('nama_surat_peminjaman');
            }
        });
    }
};
