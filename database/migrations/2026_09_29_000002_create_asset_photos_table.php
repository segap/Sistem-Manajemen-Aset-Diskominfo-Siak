<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aset', function (Blueprint $table): void {
            if (! Schema::hasColumn('aset', 'google_drive_url')) {
                $table->string('google_drive_url', 2048)->nullable()->after('path_foto');
            }
        });

        Schema::create('asset_photos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('path');
            $table->string('nama_file_asli')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('ukuran_file')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('urutan')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'is_primary']);
        });

        $now = now();
        DB::table('aset')
            ->select(['id', 'path_foto'])
            ->whereNotNull('path_foto')
            ->orderBy('id')
            ->chunkById(200, function ($assets) use ($now): void {
                $rows = [];

                foreach ($assets as $asset) {
                    $rows[] = [
                        'asset_id' => $asset->id,
                        'path' => $asset->path_foto,
                        'nama_file_asli' => basename((string) $asset->path_foto),
                        'mime_type' => null,
                        'ukuran_file' => null,
                        'is_primary' => true,
                        'urutan' => 1,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows !== []) {
                    DB::table('asset_photos')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_photos');

        Schema::table('aset', function (Blueprint $table): void {
            if (Schema::hasColumn('aset', 'google_drive_url')) {
                $table->dropColumn('google_drive_url');
            }
        });
    }
};
