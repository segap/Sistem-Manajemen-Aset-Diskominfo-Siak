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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->string('inventory_code', 50)->unique();
            $table->string('name', 150);
            $table->string('brand_type', 150)->nullable();
            $table->text('specifications')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->year('acquisition_year')->nullable();
            $table->string('condition')->default('baik')->index();
            $table->string('supplier', 150)->nullable();
            $table->string('storage_location', 150)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
