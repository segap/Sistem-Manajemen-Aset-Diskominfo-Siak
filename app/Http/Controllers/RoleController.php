<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensurePermissionSchema();

        $search = trim((string) $request->query('search', ''));

        return view('roles.index', [
            'roles' => Role::query()
                ->with('permissions:id,kode')
                ->withCount('users')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%");
                    });
                })
                ->orderByDesc('is_system')
                ->orderBy('nama')
                ->get(),
            'permissions' => Permission::query()
                ->orderBy('grup')
                ->orderBy('kode')
                ->get(['id', 'kode', 'nama', 'grup']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $this->ensurePermissionSchema();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_]+$/', 'unique:roles,kode'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'kode')],
        ], $this->messages());

        $kode = $data['kode'] ?? Str::of($data['nama'])
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', '_')
            ->trim('_')
            ->toString();

        if ($kode === '') {
            throw ValidationException::withMessages(['kode' => 'Kode role tidak valid.']);
        }

        if (Role::query()->where('kode', $kode)->exists()) {
            throw ValidationException::withMessages(['kode' => 'Kode role sudah digunakan.']);
        }

        DB::transaction(function () use ($data, $kode): void {
            $role = Role::query()->create([
                'kode' => $kode,
                'nama' => $data['nama'],
                'is_system' => false,
            ]);

            $this->syncViewPermissions($role, $data['permissions'] ?? []);
        });

        return redirect()->route('web.roles.index')->with('success', 'Role ditambahkan.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $this->ensureSuperAdmin($request);
        $this->ensurePermissionSchema();

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'kode' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('roles', 'kode')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists('permissions', 'kode')],
        ], $this->messages());

        if ($role->is_system && $data['kode'] !== $role->kode) {
            throw ValidationException::withMessages(['kode' => 'Kode role sistem tidak dapat diubah.']);
        }

        DB::transaction(function () use ($data, $role): void {
            if ($role->is_system) {
                $role->update(['nama' => $data['nama']]);
            } else {
                $oldKode = $role->kode;
                $role->update([
                    'nama' => $data['nama'],
                    'kode' => $data['kode'],
                ]);

                if ($oldKode !== $data['kode']) {
                    $role->users()->update(['role' => $data['kode']]);
                }
            }

            if ($role->kode !== UserRole::SuperAdmin->value) {
                $this->syncViewPermissions($role, $data['permissions'] ?? []);
            }
        });

        return redirect()->route('web.roles.index')->with('success', 'Role diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureSuperAdmin(request());
        if ($role->is_system) {
            throw ValidationException::withMessages(['role' => 'Role sistem tidak dapat dihapus.']);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages(['role' => 'Role ini masih digunakan pengguna. Pindahkan pengguna ke role lain terlebih dahulu.']);
        }

        $role->delete();

        return redirect()->route('web.roles.index')->with('success', 'Role berhasil dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'nama.required' => 'Nama role wajib diisi.',
            'kode.required' => 'Kode role wajib diisi.',
            'kode.regex' => 'Kode role hanya boleh huruf kecil, angka, dan underscore.',
            'kode.unique' => 'Kode role sudah digunakan.',
            'permissions.*.exists' => 'Salah satu hak akses yang dipilih tidak terdaftar.',
            'permissions.*.distinct' => 'Hak akses tidak boleh dipilih lebih dari satu kali.',
        ];
    }

    /**
     * Synchronize only menu-entry permissions exposed by the simplified UI.
     * Existing action permissions remain untouched when a role is edited.
     *
     * @param array<int, string> $codes
     */
    private function syncViewPermissions(Role $role, array $codes): void
    {
        $codes = array_values(array_unique($codes));

        if (collect($codes)->contains(fn (string $code): bool => ! str_ends_with($code, '.view'))) {
            throw ValidationException::withMessages([
                'permissions' => 'Hanya hak akses menu yang dapat diatur melalui form ini.',
            ]);
        }

        $viewPermissionIds = Permission::query()
            ->whereIn('kode', $codes)
            ->pluck('id')
            ->all();

        if (count($viewPermissionIds) !== count($codes)) {
            throw ValidationException::withMessages([
                'permissions' => 'Hak akses yang dipilih tidak valid.',
            ]);
        }

        $actionPermissionIds = $role->permissions()
            ->where('kode', 'not like', '%.view')
            ->pluck('permissions.id')
            ->all();

        $role->permissions()->sync(array_values(array_unique([
            ...$actionPermissionIds,
            ...$viewPermissionIds,
        ])));
    }

    private function ensurePermissionSchema(): void
    {
        abort_unless(
            Schema::hasTable('permissions') && Schema::hasTable('role_permissions'),
            503,
            'Struktur hak akses belum tersedia. Hubungi administrator sistem.'
        );
    }

    private function ensureSuperAdmin(Request $request): void
    {
        abort_unless(
            $request->user()?->is_active && $request->user()?->role === UserRole::SuperAdmin->value,
            403,
            'Hanya Super Admin yang dapat mengubah role dan hak akses.'
        );
    }
}
