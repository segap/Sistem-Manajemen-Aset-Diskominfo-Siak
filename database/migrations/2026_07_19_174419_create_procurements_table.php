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
        Schema::create('procurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('procurement_proposal_id')->nullable()->unique()
                ->constrained()->restrictOnDelete();
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('inventory_code', 50)->unique();
            $table->string('item_name', 150);
            $table->string('brand_type', 150)->nullable();
            $table->text('specifications');
            $table->unsignedInteger('ordered_quantity');
            $table->decimal('unit_price', 15, 2);
            $table->string('supplier', 150);
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->text('procurement_reason');
            $table->string('status')->default('dipesan')->index();
            $table->unsignedInteger('received_quantity')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('receipt_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->string('condition')->nullable();
            $table->string('storage_location', 150)->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('asset_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurements');
    }
};
