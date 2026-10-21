<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_persetujuan', function (Blueprint $table): void {
            $table->id();
            $table->string('jenis_pengajuan', 50);
            $table->unsignedBigInteger('pengajuan_id');
            $table->string('tahap', 80);
            $table->string('status', 80);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nama_user_snapshot')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('diproses_pada')->nullable();
            $table->timestamps();

            $table->index(['jenis_pengajuan', 'pengajuan_id'], 'rp_pengajuan_idx');
            $table->index(['jenis_pengajuan', 'pengajuan_id', 'diproses_pada'], 'rp_pengajuan_waktu_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_persetujuan');
    }
};
