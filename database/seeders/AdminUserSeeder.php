<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Super Administrator',
                'email' => 'SuperAdmin@gmail.com',
                'password' => '123',
                'role' => UserRole::SuperAdmin,
            ],
            [
                'name' => 'Segap Admin',
                'email' => 'segap@gmail.com',
                'password' => '123',
                'role' => UserRole::Admin,
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make($account['password']),
                    'role' => $account['role'],
                    'is_active' => true,
                ],
            );

            $this->command?->info("Akun {$account['role']->label()} tersedia: {$account['email']} / {$account['password']}");
        }
    }
}
