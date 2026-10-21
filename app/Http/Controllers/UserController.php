<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkUnit;
use App\Http\Requests\ResetUserPasswordRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = $request->string('role')->toString();
        $workUnit = $request->string('work_unit')->toString();
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        $search = $request->string('search')->toString();
        $matchingWorkUnits = collect(WorkUnit::options())
            ->filter(fn (string $label, string $value): bool => str_contains(strtolower($label), strtolower($search)) || str_contains(strtolower($value), strtolower($search)))
            ->keys()
            ->all();

        $users = User::query()
            ->with('roleData')
            ->when($request->filled('search'), function (Builder $query) use ($matchingWorkUnits, $search): void {
                $query->where(function (Builder $query) use ($matchingWorkUnits, $search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('nip', 'like', "%{$search}%")
                        ->orWhere('work_unit', 'like', "%{$search}%")
                        ->when($matchingWorkUnits !== [], fn (Builder $unitQuery) => $unitQuery->orWhereIn('work_unit', $matchingWorkUnits));
                });
            })
            ->when($role !== '', fn (Builder $query) => $query->where('role', $role))
            ->when($workUnit !== '', fn (Builder $query) => $query->where('work_unit', $workUnit))
            ->when($request->has('is_active') && $request->string('is_active')->toString() !== '', fn (Builder $query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => Role::query()->orderByDesc('is_system')->orderBy('nama')->get(),
            'workUnits' => WorkUnit::options(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        if ($request->validated('role') === UserRole::SuperAdmin->value
            && $request->user()->role !== UserRole::SuperAdmin->value) {
            throw ValidationException::withMessages(['role' => 'Hanya Super Admin yang dapat membuat Super Admin.']);
        }

        User::query()->create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active', false),
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        if (($user->role === UserRole::SuperAdmin->value || ($data['role'] ?? null) === UserRole::SuperAdmin->value)
            && $actor->role !== UserRole::SuperAdmin->value) {
            throw ValidationException::withMessages(['role' => 'Hanya Super Admin yang dapat mengubah akun Super Admin.']);
        }

        if ($actor->is($user) && $actor->role === UserRole::SuperAdmin->value
            && (($data['role'] ?? UserRole::SuperAdmin->value) !== UserRole::SuperAdmin->value
                || (array_key_exists('is_active', $data) && ! $data['is_active']))) {
            throw ValidationException::withMessages(['user' => 'Super Admin tidak dapat menurunkan role atau menonaktifkan akunnya sendiri.']);
        }

        if ($actor->is($user) && $actor->role === UserRole::Admin->value
            && ((isset($data['role']) && $data['role'] !== UserRole::Admin->value)
                || (array_key_exists('is_active', $data) && ! $data['is_active']))) {
            throw ValidationException::withMessages([
                'user' => 'Admin tidak dapat menurunkan role atau menonaktifkan akunnya sendiri.',
            ]);
        }

        $removesActiveAdmin = $user->role === UserRole::Admin->value
            && $user->is_active
            && ((isset($data['role']) && $data['role'] !== UserRole::Admin->value)
                || (array_key_exists('is_active', $data) && ! $data['is_active']));

        if ($removesActiveAdmin && User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Sistem harus memiliki minimal satu Admin aktif.',
            ]);
        }

        $user->update($data);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        if ($user->role === UserRole::SuperAdmin->value && $request->user()->role !== UserRole::SuperAdmin->value) {
            throw ValidationException::withMessages(['user' => 'Hanya Super Admin yang dapat mereset password Super Admin.']);
        }

        $user->update(['password' => $request->validated('password')]);

        return back()->with('success', 'Password pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Akun yang sedang digunakan tidak dapat dihapus.',
            ]);
        }

        if ($user->role === UserRole::Admin->value && $user->is_active && User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Sistem harus memiliki minimal satu Admin aktif.',
            ]);
        }

        try {
            DB::transaction(function () use ($user): void {
                $user->delete();
            });
        } catch (QueryException) {
            throw ValidationException::withMessages([
                'user' => 'Pengguna ini sudah memiliki riwayat transaksi, sehingga tidak dapat dihapus. Nonaktifkan akun jika tidak ingin digunakan lagi.',
            ]);
        }

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
