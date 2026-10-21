<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_peminjaman', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('peminjaman_id')->constrained('peminjaman')->cascadeOnDelete();
            $table->foreignId('aset_id')->constrained('aset')->restrictOnDelete();
            $table->string('kondisi_kembali')->nullable();
            $table->text('catatan_pengembalian')->nullable();
            $table->timestamps();

            $table->unique(['peminjaman_id', 'aset_id'], 'detail_peminjaman_unique_aset');
            $table->index('aset_id');
        });

        DB::table('peminjaman')
            ->whereNotNull('aset_id')
            ->orderBy('id')
            ->select(['id', 'aset_id', 'kondisi_kembali', 'catatan_pengembalian', 'created_at', 'updated_at'])
            ->chunkById(500, function ($loans): void {
                $now = now();
                $rows = $loans->map(fn ($loan): array => [
                    'peminjaman_id' => $loan->id,
                    'aset_id' => $loan->aset_id,
                    'kondisi_kembali' => $loan->kondisi_kembali,
                    'catatan_pengembalian' => $loan->catatan_pengembalian,
                    'created_at' => $loan->created_at ?? $now,
                    'updated_at' => $loan->updated_at ?? $now,
                ])->all();

                DB::table('detail_peminjaman')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_peminjaman');
    }
};
