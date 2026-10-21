<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('asset_condition_mutation_documents');

        Schema::table('asset_condition_mutations', function (Blueprint $table): void {
            if (! Schema::hasColumn('asset_condition_mutations', 'google_drive_url')) {
                $table->string('google_drive_url', 2048)->nullable()->after('catatan');
            }
        });

        Schema::create('asset_condition_mutation_documents', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('asset_condition_mutation_id');
            $table->string('jenis', 20);
            $table->string('path_file');
            $table->string('nama_file_asli')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('ukuran_file')->nullable();
            $table->timestamps();

            $table->index(['asset_condition_mutation_id', 'jenis'], 'asset_mutation_docs_mutation_jenis_idx');
            $table->foreign('asset_condition_mutation_id', 'asset_mutation_docs_mutation_fk')
                ->references('id')
                ->on('asset_condition_mutations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_condition_mutation_documents');

        Schema::table('asset_condition_mutations', function (Blueprint $table): void {
            if (Schema::hasColumn('asset_condition_mutations', 'google_drive_url')) {
                $table->dropColumn('google_drive_url');
            }
        });
    }
};
