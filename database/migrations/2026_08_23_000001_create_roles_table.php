<?php

use App\Enums\UserRole;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();
        DB::table('roles')->insert(array_map(fn (UserRole $role): array => [
            'kode' => $role->value,
            'nama' => $role->label(),
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], UserRole::cases()));
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
