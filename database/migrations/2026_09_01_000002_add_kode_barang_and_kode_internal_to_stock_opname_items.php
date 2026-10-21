<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->dropUnique('stock_opname_items_kode_persediaan_unique');
        });

        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->foreignId('kode_barang_id')->nullable()->after('kode_persediaan_id')->constrained('kode_barang')->nullOnDelete();
            $table->string('kode_internal', 50)->nullable()->unique()->after('kode_barang_id');
        });

        $usedCodes = [];
        DB::table('stock_opname_items')
            ->orderBy('id')
            ->get(['id', 'nama_barang', 'kode_persediaan'])
            ->each(function (object $item) use (&$usedCodes): void {
                $kodeBarangId = DB::table('kode_barang')
                    ->where('kode_barang', $item->kode_persediaan)
                    ->value('id');
                $prefix = $this->prefix((string) $item->nama_barang);
                $sequence = ($usedCodes[$prefix] ?? 0) + 1;

                do {
                    $code = sprintf('%s/%03d', $prefix, $sequence++);
                } while (DB::table('stock_opname_items')->where('kode_internal', $code)->exists());

                $usedCodes[$prefix] = $sequence - 1;

                DB::table('stock_opname_items')
                    ->where('id', $item->id)
                    ->update([
                        'kode_barang_id' => $kodeBarangId,
                        'kode_internal' => $code,
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('kode_barang_id');
            $table->dropUnique(['kode_internal']);
            $table->dropColumn('kode_internal');
            $table->unique('kode_persediaan');
        });
    }

    private function prefix(string $itemName): string
    {
        $cleanName = preg_replace('/[^A-Za-z0-9\s]/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT', $itemName) ?: $itemName) ?: '';
        $words = array_values(array_filter(preg_split('/\s+/', trim($cleanName)) ?: []));

        if (count($words) >= 2) {
            return implode('', array_map(fn (string $word): string => strtoupper(substr($word, 0, 1)), $words));
        }

        return substr(strtoupper($words[0] ?? 'BRG'), 0, 3);
    }
};
