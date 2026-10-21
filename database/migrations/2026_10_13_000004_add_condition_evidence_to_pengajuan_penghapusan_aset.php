<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengajuan_penghapusan_aset', function (Blueprint $table): void {
            if (! Schema::hasColumn('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')) {
                $table->unsignedBigInteger('asset_condition_mutation_id')->nullable()->after('aset_id');
            }

            if (! Schema::hasColumn('pengajuan_penghapusan_aset', 'catatan_kondisi_snapshot')) {
                $table->text('catatan_kondisi_snapshot')->nullable()->after('catatan');
            }

            if (! Schema::hasColumn('pengajuan_penghapusan_aset', 'google_drive_url_snapshot')) {
                $table->string('google_drive_url_snapshot', 2048)->nullable()->after('catatan_kondisi_snapshot');
            }
        });

        if (
            Schema::hasColumn('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')
            && ! $this->foreignKeyOnColumnExists('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')
        ) {
            Schema::table('pengajuan_penghapusan_aset', function (Blueprint $table): void {
                $table->foreign('asset_condition_mutation_id', 'fk_penghapusan_mutation')
                    ->references('id')
                    ->on('asset_condition_mutations')
                    ->nullOnDelete();
            });
        }

        if ($this->isColumnNotNullable('pengajuan_penghapusan_aset', 'path_dokumen')) {
            DB::statement('ALTER TABLE pengajuan_penghapusan_aset MODIFY path_dokumen VARCHAR(255) NULL');
        }

        if ($this->isColumnNotNullable('pengajuan_penghapusan_aset', 'nama_dokumen')) {
            DB::statement('ALTER TABLE pengajuan_penghapusan_aset MODIFY nama_dokumen VARCHAR(255) NULL');
        }

        if ($this->isColumnNotNullable('pengajuan_penghapusan_aset', 'mime_dokumen')) {
            DB::statement('ALTER TABLE pengajuan_penghapusan_aset MODIFY mime_dokumen VARCHAR(100) NULL');
        }

        if (! Schema::hasTable('pengajuan_penghapusan_bukti')) {
            Schema::create('pengajuan_penghapusan_bukti', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('pengajuan_penghapusan_aset_id');
                $table->unsignedBigInteger('asset_condition_mutation_document_id')->nullable();
                $table->string('jenis', 20);
                $table->string('path_file');
                $table->string('nama_file')->nullable();
                $table->string('mime_type')->nullable();
                $table->unsignedBigInteger('ukuran_file')->nullable();
                $table->timestamps();

                $table->index(['pengajuan_penghapusan_aset_id', 'jenis'], 'bukti_penghapusan_jenis_idx');
            });
        }

        if (Schema::hasTable('pengajuan_penghapusan_bukti')) {
            Schema::table('pengajuan_penghapusan_bukti', function (Blueprint $table): void {
                if (! $this->foreignKeyExists('pengajuan_penghapusan_bukti', 'fk_bukti_penghapusan')) {
                    $table->foreign('pengajuan_penghapusan_aset_id', 'fk_bukti_penghapusan')
                        ->references('id')
                        ->on('pengajuan_penghapusan_aset')
                        ->cascadeOnDelete();
                }

                if (! $this->foreignKeyExists('pengajuan_penghapusan_bukti', 'fk_bukti_mutation_doc')) {
                    $table->foreign('asset_condition_mutation_document_id', 'fk_bukti_mutation_doc')
                        ->references('id')
                        ->on('asset_condition_mutation_documents')
                        ->nullOnDelete();
                }

                if (! $this->indexExists('pengajuan_penghapusan_bukti', 'bukti_penghapusan_jenis_idx')) {
                    $table->index(['pengajuan_penghapusan_aset_id', 'jenis'], 'bukti_penghapusan_jenis_idx');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_penghapusan_bukti');

        Schema::table('pengajuan_penghapusan_aset', function (Blueprint $table): void {
            if (Schema::hasColumn('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')) {
                if ($this->foreignKeyExists('pengajuan_penghapusan_aset', 'fk_penghapusan_mutation')) {
                    $table->dropForeign('fk_penghapusan_mutation');
                }

                $table->dropColumn('asset_condition_mutation_id');
            }

            foreach (['google_drive_url_snapshot', 'catatan_kondisi_snapshot'] as $column) {
                if (Schema::hasColumn('pengajuan_penghapusan_aset', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function isColumnNotNullable(string $table, string $column): bool
    {
        if (! Schema::hasColumn($table, $column)) {
            return false;
        }

        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$database, $table, $column],
        );

        return strtoupper((string) ($result->IS_NULLABLE ?? 'YES')) === 'NO';
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$database, $table, $constraint, 'FOREIGN KEY'],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }

    private function foreignKeyOnColumnExists(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table, $column],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $database = DB::getDatabaseName();
        $result = DB::selectOne(
            'SELECT COUNT(*) AS aggregate FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$database, $table, $index],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
