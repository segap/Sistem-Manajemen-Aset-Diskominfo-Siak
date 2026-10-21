<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kode_barang', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('kode_barang')->nullOnDelete();
            $table->string('kode_barang', 100)->unique();
            $table->string('akun', 150)->nullable()->index();
            $table->string('kelompok', 150)->nullable()->index();
            $table->string('jenis', 150)->nullable()->index();
            $table->string('objek', 150)->nullable();
            $table->string('rincian_objek', 150)->nullable();
            $table->string('sub_rincian_objek', 150)->nullable();
            $table->string('sub_sub_rincian_objek', 150)->nullable();
            $table->string('nama_barang', 200);
            $table->unsignedSmallInteger('masa_manfaat')->nullable();
            $table->decimal('batas_kapitalisasi', 18, 2)->nullable();
            $table->timestamps();

            $table->index(['nama_barang', 'kode_barang']);
        });

        Schema::create('kode_persediaan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('kode_persediaan')->nullOnDelete();
            $table->string('kode_persediaan', 100)->unique();
            $table->string('akun', 150)->nullable()->index();
            $table->string('kelompok', 150)->nullable()->index();
            $table->string('jenis', 150)->nullable()->index();
            $table->string('objek', 150)->nullable();
            $table->string('rincian_objek', 150)->nullable();
            $table->string('sub_rincian_objek', 150)->nullable();
            $table->string('sub_sub_rincian_objek', 150)->nullable();
            $table->string('nama_barang', 200);
            $table->unsignedSmallInteger('masa_manfaat')->nullable();
            $table->decimal('batas_kapitalisasi', 18, 2)->nullable();
            $table->timestamps();

            $table->index(['nama_barang', 'kode_persediaan']);
        });

        Schema::create('kode_lokasi', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('kode_lokasi')->nullOnDelete();
            $table->string('kode_lokasi', 100)->unique();
            $table->string('nama_lokasi', 200);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['nama_lokasi', 'kode_lokasi']);
        });

        Schema::table('aset', function (Blueprint $table): void {
            $table->foreignId('kode_barang_id')->nullable()->after('kategori_aset_id')->constrained('kode_barang')->nullOnDelete();
        });

        Schema::table('ruangan', function (Blueprint $table): void {
            $table->foreignId('kode_lokasi_id')->nullable()->after('kode_ruangan')->constrained('kode_lokasi')->nullOnDelete();
        });

        Schema::table('kir', function (Blueprint $table): void {
            $table->foreignId('kode_lokasi_id')->nullable()->after('ruangan_id')->constrained('kode_lokasi')->nullOnDelete();
        });

        Schema::table('detail_kir', function (Blueprint $table): void {
            $table->foreignId('kode_barang_id')->nullable()->after('aset_id')->constrained('kode_barang')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('detail_kir', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kode_barang_id');
        });

        Schema::table('kir', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kode_lokasi_id');
        });

        Schema::table('ruangan', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kode_lokasi_id');
        });

        Schema::table('aset', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kode_barang_id');
        });

        Schema::dropIfExists('kode_lokasi');
        Schema::dropIfExists('kode_persediaan');
        Schema::dropIfExists('kode_barang');
    }
};
