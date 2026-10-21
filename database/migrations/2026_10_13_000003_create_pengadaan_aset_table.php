<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('pengadaan_aset')) {
            return;
        }

        Schema::create('pengadaan_aset', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pengadaan_id')->constrained('pengadaan')->cascadeOnDelete();
            $table->foreignId('aset_id')->constrained('aset')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['pengadaan_id', 'aset_id']);
            $table->index('aset_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengadaan_aset');
    }
};
