<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->char('file_hash', 64)->unique();
            $table->string('source_filename', 255);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('processing')->index();
            $table->unsignedInteger('asset_unit_count')->default(0);
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_import_batches');
    }
};
