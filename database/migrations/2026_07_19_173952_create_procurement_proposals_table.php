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
        Schema::create('procurement_proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->string('item_name', 150);
            $table->text('specifications');
            $table->unsignedInteger('quantity');
            $table->decimal('estimated_unit_price', 15, 2);
            $table->text('technical_justification');
            $table->foreignId('intended_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('responsible_person_id')->constrained('users')->restrictOnDelete();
            $table->string('image_reference_url', 2048)->nullable();
            $table->string('ecatalog_url', 2048)->nullable();
            $table->string('photo_path');
            $table->string('status')->default('diajukan')->index();
            $table->foreignId('asset_team_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('asset_team_reviewed_at')->nullable();
            $table->text('asset_team_notes')->nullable();
            $table->foreignId('supervisor_reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('supervisor_reviewed_at')->nullable();
            $table->text('supervisor_notes')->nullable();
            $table->string('rejected_at_stage')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('procurement_proposals');
    }
};
