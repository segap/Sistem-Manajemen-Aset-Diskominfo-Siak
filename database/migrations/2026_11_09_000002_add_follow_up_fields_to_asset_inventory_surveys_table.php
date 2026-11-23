<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_inventory_surveys', function (Blueprint $table): void {
            $table->string('status', 30)->default('aktif')->after('baris_excel');
            $table->timestamp('ditindaklanjuti_pada')->nullable()->after('status');
            $table->foreignId('ditindaklanjuti_oleh')->nullable()->after('ditindaklanjuti_pada')->constrained('users')->nullOnDelete();
            $table->foreignId('asset_id')->nullable()->after('ditindaklanjuti_oleh')->constrained('aset')->nullOnDelete();
            $table->index(['status', 'kategori_aset_id'], 'asset_inventory_survey_status_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('asset_inventory_surveys', function (Blueprint $table): void {
            $table->dropIndex('asset_inventory_survey_status_lookup_idx');
            $table->dropConstrainedForeignId('asset_id');
            $table->dropConstrainedForeignId('ditindaklanjuti_oleh');
            $table->dropColumn(['ditindaklanjuti_pada', 'status']);
        });
    }
};
