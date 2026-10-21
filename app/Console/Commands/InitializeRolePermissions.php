<?php

namespace App\Console\Commands;

use App\Services\PermissionInitializer;
use Illuminate\Console\Command;
use LogicException;

class InitializeRolePermissions extends Command
{
    protected $signature = 'permissions:initialize {--force : Jalankan inisialisasi katalog dan mapping role kosong}';

    protected $description = 'Inisialisasi katalog permission dan mapping role lama tanpa menimpa konfigurasi yang sudah ada';

    public function handle(PermissionInitializer $initializer): int
    {
        if (! $this->option('force')) {
            $this->error('Perintah ini menulis katalog permission. Jalankan ulang dengan --force setelah backup dan review.');

            return self::FAILURE;
        }

        try {
            $result = $initializer->initializeEmptyRoles();
        } catch (LogicException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Permission baru dibuat: {$result['permissions_created']}");
        $this->line('Role diinisialisasi: '.($result['roles_initialized'] === [] ? '-' : implode(', ', $result['roles_initialized'])));
        $this->line('Role dilewati: '.($result['roles_skipped'] === [] ? '-' : implode(', ', $result['roles_skipped'])));

        return self::SUCCESS;
    }
}
