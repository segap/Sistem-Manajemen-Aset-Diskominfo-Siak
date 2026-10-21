<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $conditions = ['baik', 'rusak_ringan', 'rusak_sedang', 'rusak_berat', 'hilang'];

    public function up(): void
    {
        Schema::create('asset_condition_stocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('kondisi', 30);
            $table->unsignedInteger('jumlah')->default(0);
            $table->timestamps();

            $table->unique(['asset_id', 'kondisi']);
        });

        Schema::create('asset_condition_mutations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('asset_id')->constrained('aset')->cascadeOnDelete();
            $table->string('dari_kondisi', 30);
            $table->string('ke_kondisi', 30);
            $table->unsignedInteger('jumlah');
            $table->text('catatan')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        $now = now();

        DB::table('aset')
            ->select(['id', 'jumlah', 'kondisi'])
            ->orderBy('id')
            ->chunkById(200, function ($assets) use ($now): void {
                $rows = [];

                foreach ($assets as $asset) {
                    $assetCondition = in_array($asset->kondisi, $this->conditions, true) ? $asset->kondisi : 'baik';

                    foreach ($this->conditions as $condition) {
                        $rows[] = [
                            'asset_id' => $asset->id,
                            'kondisi' => $condition,
                            'jumlah' => $condition === $assetCondition ? (int) $asset->jumlah : 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($rows !== []) {
                    DB::table('asset_condition_stocks')->insert($rows);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_condition_mutations');
        Schema::dropIfExists('asset_condition_stocks');
    }
};
