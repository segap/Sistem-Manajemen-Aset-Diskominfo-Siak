<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_permissions')) {
            return;
        }

        Schema::create('role_permissions', function (Blueprint $table): void {
            $table->foreignId('role_id')
                ->constrained('roles', 'id', 'rp_role_fk')
                ->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained('permissions', 'id', 'rp_permission_fk')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['role_id', 'permission_id'], 'rp_role_permission_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
