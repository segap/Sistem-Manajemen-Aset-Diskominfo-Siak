<?php

namespace App\Models;

use BackedEnum;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Services\PermissionService;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** @var list<string> */
    protected $fillable = ['name', 'nip', 'email', 'password', 'role', 'work_unit', 'is_active'];

    /** @var list<string> */
    protected $hidden = ['password', 'remember_token'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_tanggal_verifikasi' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function roleData(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role', 'kode');
    }

    public function hasPermission(string $permission): bool
    {
        return app(PermissionService::class)->allows($this, $permission);
    }

    public function setRoleAttribute(mixed $value): void
    {
        $this->attributes['role'] = $value instanceof BackedEnum ? $value->value : $value;
    }
}
