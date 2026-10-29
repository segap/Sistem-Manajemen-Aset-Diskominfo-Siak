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

            $this->syncMenuAndManagedActionPermissions($role, $data['permissions'] ?? []);
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
                $this->syncMenuAndManagedActionPermissions($role, $data['permissions'] ?? []);
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
     * Synchronize menu permissions plus explicitly managed module actions.
     * exposed by the role form. Actions from every other module remain intact.
     *
     * @param array<int, string> $codes
     */
    private function syncMenuAndManagedActionPermissions(Role $role, array $codes): void
    {
        $codes = array_values(array_unique($codes));
        $assetActionCodes = [
            'aset.create',
            'aset.update',
            'aset.condition.update',
            'aset.export',
            'aset.import',
            'aset.delete',
        ];
        $categoryActionCodes = [
            'kategori_aset.create',
            'kategori_aset.update',
            'kategori_aset.delete',
            'kategori_aset.export.excel',
            'kategori_aset.export.pdf',
        ];
        $kodefikasiActionCodes = [
            'kodefikasi.create',
            'kodefikasi.update',
            'kodefikasi.delete',
            'kodefikasi.import',
            'kodefikasi.export',
        ];
        $kodefikasiInternalActionCodes = [
            'kodefikasi_internal.generate',
            'kodefikasi_internal.bulk_generate',
            'kodefikasi_internal.update',
            'kodefikasi_internal.reset',
            'kodefikasi_internal.export',
        ];
        $ruanganActionCodes = [
            'ruangan.create',
            'ruangan.update',
            'ruangan.delete',
            'ruangan.export',
        ];
        $kirActionCodes = [
            'kir.create',
            'kir.update',
            'kir.delete',
            'kir.import',
            'kir.template.download',
            'kir.download',
            'kir.export',
        ];
        $stockOpnameActionCodes = [
            'stock_opname.create',
            'stock_opname.update',
            'stock_opname.import',
            'stock_opname.template.download',
            'stock_opname.export',
            'stock_opname.stock.add',
            'stock_opname.stock.adjust',
            'stock_opname.verify',
        ];
        $stockOpnameCategoryActionCodes = [
            'kategori_stock_opname.create',
            'kategori_stock_opname.update',
            'kategori_stock_opname.delete',
            'kategori_stock_opname.export',
        ];
        $supplyRequestActionCodes = [
            'permintaan.create',
            'permintaan.export',
            'permintaan.verify',
            'permintaan.handover',
        ];
        $loanActionCodes = [
            'peminjaman.create',
            'peminjaman.verify',
            'peminjaman.handover',
            'peminjaman.return',
            'peminjaman.export',
        ];
        $maintenanceActionCodes = [
            'pemeliharaan.create',
            'pemeliharaan.update',
            'pemeliharaan.download',
            'pemeliharaan.export',
            'pemeliharaan.verify',
            'pemeliharaan.start',
            'pemeliharaan.complete',
            'pemeliharaan.report.update',
        ];
        $procurementProposalActionCodes = [
            'usulan_pengadaan.create',
            'usulan_pengadaan.verify',
            'usulan_pengadaan.export',
        ];
        $procurementActionCodes = [
            'pengadaan.create',
            'pengadaan.update',
            'pengadaan.receive',
            'pengadaan.verify',
            'pengadaan.export',
        ];
        $disposalActionCodes = [
            'penghapusan.create',
            'penghapusan.approve',
            'penghapusan.download',
            'penghapusan.export',
        ];
        $reportActionCodesByView = [
            'laporan_aset.view' => ['laporan_aset.export'],
            'laporan_rekonsiliasi_bulanan.view' => ['laporan_rekonsiliasi_bulanan.upload', 'laporan_rekonsiliasi_bulanan.delete', 'laporan_rekonsiliasi_bulanan.export'],
            'laporan_semester_1.view' => ['laporan_semester_1.upload', 'laporan_semester_1.delete', 'laporan_semester_1.export'],
            'laporan_tahunan.view' => ['laporan_tahunan.upload', 'laporan_tahunan.delete', 'laporan_tahunan.export'],
            'laporan_aset_unaudited.view' => ['laporan_aset_unaudited.upload', 'laporan_aset_unaudited.delete', 'laporan_aset_unaudited.export'],
            'laporan_aset_audited.view' => ['laporan_aset_audited.upload', 'laporan_aset_audited.delete', 'laporan_aset_audited.export'],
            'laporan_rkbmd.view' => ['laporan_rkbmd.upload', 'laporan_rkbmd.download', 'laporan_rkbmd.delete', 'laporan_rkbmd.export'],
            'berita_acara_bmd.view' => ['berita_acara_bmd.upload', 'berita_acara_bmd.complete', 'berita_acara_bmd.delete', 'berita_acara_bmd.export'],
            'laporan_persediaan_bulanan.view' => ['laporan_persediaan_bulanan.upload', 'laporan_persediaan_bulanan.delete', 'laporan_persediaan_bulanan.export'],
            'laporan_persediaan_semester_1.view' => ['laporan_persediaan_semester_1.upload', 'laporan_persediaan_semester_1.delete', 'laporan_persediaan_semester_1.export'],
            'laporan_persediaan_tahunan.view' => ['laporan_persediaan_tahunan.upload', 'laporan_persediaan_tahunan.delete', 'laporan_persediaan_tahunan.export'],
        ];
        $reportActionCodes = array_merge(...array_values($reportActionCodesByView));
        $managedActionCodes = [...$assetActionCodes, ...$categoryActionCodes, ...$kodefikasiActionCodes, ...$kodefikasiInternalActionCodes, ...$ruanganActionCodes, ...$kirActionCodes, ...$stockOpnameActionCodes, ...$stockOpnameCategoryActionCodes, ...$supplyRequestActionCodes, ...$loanActionCodes, ...$maintenanceActionCodes, ...$procurementProposalActionCodes, ...$procurementActionCodes, ...$disposalActionCodes, ...$reportActionCodes];

        if (collect($codes)->contains(fn (string $code): bool => ! str_ends_with($code, '.view') && ! in_array($code, $managedActionCodes, true))) {
            throw ValidationException::withMessages([
                'permissions' => 'Hak tindakan yang dipilih tidak dapat diatur melalui form ini.',
            ]);
        }

        if (! in_array('aset.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $assetActionCodes));
        }
        if (! in_array('kategori_aset.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $categoryActionCodes));
        }
        if (! in_array('kodefikasi.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $kodefikasiActionCodes));
        }
        if (! in_array('kodefikasi_internal.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $kodefikasiInternalActionCodes));
        }
        if (! in_array('ruangan.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $ruanganActionCodes));
        }
        if (! in_array('kir.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $kirActionCodes));
        }
        if (! in_array('stock_opname.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $stockOpnameActionCodes));
        }
        if (! in_array('kategori_stock_opname.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $stockOpnameCategoryActionCodes));
        }
        if (! in_array('permintaan.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $supplyRequestActionCodes));
        }
        if (! in_array('peminjaman.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $loanActionCodes));
        }
        if (! in_array('pemeliharaan.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $maintenanceActionCodes));
        }
        if (! in_array('usulan_pengadaan.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $procurementProposalActionCodes));
        }
        if (! in_array('pengadaan.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $procurementActionCodes));
        }
        if (! in_array('penghapusan.view', $codes, true)) {
            $codes = array_values(array_diff($codes, $disposalActionCodes));
        }
        foreach ($reportActionCodesByView as $viewCode => $actionCodes) {
            if (! in_array($viewCode, $codes, true)) {
                $codes = array_values(array_diff($codes, $actionCodes));
            }
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

        $preservedActionPermissionIds = $role->permissions()
            ->where('kode', 'not like', '%.view')
            ->whereNotIn('kode', $managedActionCodes)
            ->pluck('permissions.id')
            ->all();

        $role->permissions()->sync(array_values(array_unique([
            ...$preservedActionPermissionIds,
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
