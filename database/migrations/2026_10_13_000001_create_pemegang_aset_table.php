<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pemegang_aset', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('nama_pegawai', 150);
            $table->string('nip', 30)->nullable()->unique();
            $table->string('email')->nullable()->unique();
            $table->string('unit_kerja', 80)->index();
            $table->string('jabatan', 150);
            $table->string('nomor_hp', 30)->nullable();
            $table->string('foto_path')->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('ktp_path')->nullable();
            $table->string('ktp_nama_file')->nullable();
            $table->string('ktp_mime', 100)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();

            $table->index('nama_pegawai');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pemegang_aset');
    }
};
