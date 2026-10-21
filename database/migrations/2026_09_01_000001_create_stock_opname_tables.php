<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_kategori', 120)->unique();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_category_id')->constrained('stock_opname_categories')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('kode_persediaan_id')->nullable()->constrained('kode_persediaan')->nullOnDelete();
            $table->foreignId('pengusul_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('diverifikasi_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kode_persediaan', 100);
            $table->string('nama_barang', 150);
            $table->string('merek_tipe', 150)->nullable();
            $table->string('satuan', 40);
            $table->unsignedInteger('stok')->default(0);
            $table->string('lokasi_penyimpanan', 150)->nullable();
            $table->text('keterangan')->nullable();
            $table->string('status_verifikasi', 40)->default('diajukan');
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->text('catatan_verifikasi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('kode_persediaan');
            $table->index(['status_verifikasi', 'is_active', 'stok']);
        });

        Schema::create('stock_opname_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_item_id')->constrained('stock_opname_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('aksi', 80);
            $table->string('status_sebelum', 40)->nullable();
            $table->string('status_sesudah', 40)->nullable();
            $table->integer('stok_sistem')->nullable();
            $table->integer('stok_fisik')->nullable();
            $table->integer('selisih')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_opname_mutations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stock_opname_item_id')->constrained('stock_opname_items')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis', 40);
            $table->unsignedInteger('jumlah');
            $table->unsignedInteger('stok_sebelum');
            $table->unsignedInteger('stok_sesudah');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });

        DB::table('stock_opname_categories')->insert([
            ['nama_kategori' => 'Barang Habis Pakai', 'keterangan' => 'Kategori umum barang persediaan habis pakai.', 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Alat Tulis Kantor', 'keterangan' => 'Persediaan alat tulis dan kebutuhan administrasi.', 'created_at' => now(), 'updated_at' => now()],
            ['nama_kategori' => 'Bahan Operasional', 'keterangan' => 'Persediaan bahan pendukung operasional kantor.', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_mutations');
        Schema::dropIfExists('stock_opname_histories');
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opname_categories');
    }
};
