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
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->foreignId('handed_over_by')->nullable()->after('rejection_reason')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('handed_over_at')->nullable()->after('handed_over_by');
            $table->foreignId('returned_by')->nullable()->after('handed_over_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('returned_at')->nullable()->after('returned_by');
            $table->string('return_condition')->nullable()->after('returned_at');
            $table->text('return_notes')->nullable()->after('return_condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('loan_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handed_over_by');
            $table->dropConstrainedForeignId('returned_by');
            $table->dropColumn(['handed_over_at', 'returned_at', 'return_condition', 'return_notes']);
        });
    }
};
