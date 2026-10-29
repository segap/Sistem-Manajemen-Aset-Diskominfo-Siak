<?php

namespace App\Http\Controllers;

use App\Enums\AssetCondition;
use App\Enums\AssetMaintenanceStatus;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Http\Requests\DecideLoanRequest;
use App\Http\Requests\ReturnLoanRequest;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Asset;
use App\Models\DetailPeminjaman;
use App\Models\LoanRequest;
use App\Services\AssetConditionStockService;
use App\Services\RiwayatPersetujuanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class LoanRequestController extends Controller
{
    public function index(Request $request): View
    {
        return $this->renderIndex($request);
    }

    public function verification(Request $request): View
    {
        return $this->renderIndex($request, [
            'pageTitle' => 'Verifikasi Peminjaman',
            'pageSubtitle' => 'Tinjau pengajuan peminjaman barang sebelum diteruskan ke persetujuan Kepala Sub Bagian Perencanaan dan Umum.',
            'indexRouteName' => 'web.peminjaman.verifikasi.index',
            'statusFilterLabels' => [
                LoanStatus::Submitted->value => 'Menunggu Verifikasi',
                LoanStatus::VerifiedByAssetTeam->value => 'Terverifikasi Tim Aset',
                LoanStatus::Approved->value => 'Disetujui Kasubbag',
                LoanStatus::LegacyApproved->value => 'Disetujui',
                LoanStatus::Rejected->value => 'Ditolak',
                LoanStatus::Borrowed->value => 'Dipinjam',
                LoanStatus::Returned->value => 'Dikembalikan',
            ],
        ]);
    }

    private function renderIndex(Request $request, array $viewData = []): View
    {
        $user = $request->user();
        $role = $this->roleValue($user?->role);
        $perPage = min(max($request->integer('per_page', 10), 1), 100);
        if ($role === UserRole::KasubbagUmumPerencanaan->value && ! $request->has('status')) {
            $request->merge(['status' => LoanStatus::VerifiedByAssetTeam->value]);
        }
        $status = LoanStatus::tryFrom($request->string('status')->toString());

        $loans = LoanRequest::query()
            ->with(['requester', 'asset.category', 'asset.thumbnailPhoto', 'details.asset.category', 'details.asset.thumbnailPhoto', 'reviewer', 'handedOverBy', 'returnedBy'])
            ->when($role === UserRole::AdminBidang->value, fn (Builder $query) => $query->where('pemohon_id', $user->id))
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query
                        ->where('alasan', 'like', "%{$search}%")
                        ->orWhereHas('requester', function (Builder $query) use ($search): void {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('nip', 'like', "%{$search}%")
                                ->orWhere('work_unit', 'like', "%{$search}%");
                        })
                        ->orWhereHas('asset', function (Builder $query) use ($search): void {
                            $query
                                ->where('kode_internal', 'like', "%{$search}%")
                                ->orWhere('nama_aset', 'like', "%{$search}%")
                                ->orWhere('merek_tipe', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', [LoanStatus::Submitted->value])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('peminjaman.index', array_merge([
            'loans' => $loans,
            'assetGroups' => $this->borrowableAssetGroups(),
            'statuses' => LoanStatus::cases(),
            'conditions' => AssetCondition::cases(),
            'pageTitle' => 'Peminjaman Barang',
            'pageSubtitle' => 'Kelola pengajuan, persetujuan, penyerahan, hingga pengembalian aset.',
            'indexRouteName' => 'web.peminjaman.index',
        ], $viewData));
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasPermission('peminjaman.create'),
            403,
            'Anda tidak memiliki hak akses untuk mengajukan peminjaman.',
        );

        $data = $request->validated();
        $items = collect($data['items'])->values();
        unset($data['items']);

        $duplicateGroup = $items
            ->pluck('group_key')
            ->filter()
            ->duplicates()
            ->isNotEmpty();

        if ($duplicateGroup) {
            throw ValidationException::withMessages([
                'items' => 'Barang yang sama cukup dipilih satu kali. Gunakan kolom jumlah untuk meminta lebih dari satu unit.',
            ]);
        }

        $storedLetterPath = null;

        if ($request->hasFile('surat_peminjaman')) {
            $letterFile = $request->file('surat_peminjaman');
            $storedLetterPath = $letterFile->store('peminjaman/surat', 'public');
            $data['surat_peminjaman'] = $storedLetterPath;
            $data['nama_surat_peminjaman'] = $letterFile->getClientOriginalName();
        } else {
            unset($data['surat_peminjaman']);
            unset($data['nama_surat_peminjaman']);
        }

        try {
            $loan = DB::transaction(function () use ($request, $data, $items): LoanRequest {
                $allocatedAssets = collect();
                $allocatedAssetIds = [];

                foreach ($items as $item) {
                    $assets = $this->allocateAssetsForGroup(
                        (string) $item['group_key'],
                        (int) $item['quantity'],
                        $data['tanggal_pinjam'],
                        $data['tanggal_kembali'],
                        $allocatedAssetIds,
                    );

                    foreach ($assets as $asset) {
                        $allocatedAssets->push($asset);
                        $allocatedAssetIds[] = (int) $asset->id;
                    }
                }

                $firstAssetId = (int) $allocatedAssets->first()->id;
                $loan = LoanRequest::query()->create([
                    ...$data,
                    // Legacy compatibility: source of truth baru ada di detail_peminjaman.
                    'aset_id' => $firstAssetId,
                    'jumlah' => $allocatedAssets->count(),
                    'pemohon_id' => $request->user()->id,
                    'status' => LoanStatus::Submitted,
                ]);

                $loan->details()->createMany($allocatedAssets->map(fn (Asset $asset): array => [
                    'aset_id' => (int) $asset->id,
                ])->all());

                return $loan->load(['details.asset.category', 'details.asset.thumbnailPhoto', 'asset.category', 'asset.thumbnailPhoto']);
            });
        } catch (Throwable $exception) {
            if ($storedLetterPath) {
                Storage::disk('public')->delete($storedLetterPath);
            }

            throw $exception;
        }

        app(RiwayatPersetujuanService::class)->record('loan', $loan, 'pengajuan', 'dibuat', $loan->alasan, $request->user());

        return back()->with('success', 'Pengajuan peminjaman berhasil dikirim.');
    }

    public function show(Request $request, LoanRequest $loanRequest): View
    {
        $this->authorizeView($request, $loanRequest);

        return view('peminjaman.show', [
            'loan' => $loanRequest->load(['requester', 'asset.category', 'asset.thumbnailPhoto', 'details.asset.category', 'details.asset.thumbnailPhoto', 'reviewer', 'handedOverBy', 'returnedBy']),
            'conditions' => AssetCondition::cases(),
        ]);
    }

    public function decide(DecideLoanRequest $request, LoanRequest $loanRequest): RedirectResponse
    {
        $role = $this->roleValue($request->user()?->role);
        $decision = LoanStatus::from($request->validated('decision'));
        $processedAsVerification = false;

        DB::transaction(function () use ($request, $loanRequest, $decision, $role, &$processedAsVerification): void {
            $loan = LoanRequest::query()->lockForUpdate()->findOrFail($loanRequest->id);

            if ($loan->status === LoanStatus::Submitted) {
                abort_unless(
                    $request->user()?->hasPermission('peminjaman.verify'),
                    403,
                    'Anda tidak memiliki hak akses untuk memverifikasi peminjaman.',
                );

                $this->decideAsAssetTeam($request, $loan, $decision);
                $processedAsVerification = true;

                return;
            }

            if ($loan->status === LoanStatus::VerifiedByAssetTeam && $role === UserRole::KasubbagUmumPerencanaan->value) {
                $this->decideAsSupervisor($request, $loan, $decision);

                return;
            }

            abort(403, 'Anda tidak memiliki akses untuk memproses persetujuan peminjaman.');
        });

        return back()->with('success', match (true) {
            $processedAsVerification && $decision === LoanStatus::VerifiedByAssetTeam => 'Pengajuan diverifikasi Tim Aset.',
            $processedAsVerification && $decision === LoanStatus::Rejected => 'Pengajuan ditolak Tim Aset.',
            $decision === LoanStatus::Approved => 'Pengajuan disetujui Kasubbag.',
            default => 'Pengajuan ditolak Kasubbag.',
        });
    }

    private function decideAsAssetTeam(DecideLoanRequest $request, LoanRequest $loan, LoanStatus $decision): void
    {
        if (! in_array($decision, [LoanStatus::VerifiedByAssetTeam, LoanStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan Tim Aset tidak valid.']);
        }

        if ($loan->status !== LoanStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini hanya dapat diverifikasi Tim Aset saat masih berstatus diajukan.']);
        }

        $loan->update([
            'status' => $decision,
            'diperiksa_oleh' => $request->user()->id,
            'tanggal_pemeriksaan' => now(),
            'alasan_penolakan' => $decision === LoanStatus::Rejected
                ? $request->validated('alasan_penolakan')
                : null,
        ]);

        app(RiwayatPersetujuanService::class)->record(
            'loan',
            $loan,
            'verifikasi_tim_aset',
            $decision === LoanStatus::VerifiedByAssetTeam ? 'disetujui' : 'ditolak',
            $decision === LoanStatus::Rejected ? $request->validated('alasan_penolakan') : null,
            $request->user(),
        );
    }

    private function decideAsSupervisor(DecideLoanRequest $request, LoanRequest $loan, LoanStatus $decision): void
    {
        if (! in_array($decision, [LoanStatus::Approved, LoanStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'Keputusan Kasubbag tidak valid.']);
        }

        if ($loan->status !== LoanStatus::VerifiedByAssetTeam) {
            throw ValidationException::withMessages(['status' => 'Pengajuan harus diverifikasi Tim Aset terlebih dahulu.']);
        }

        if ($decision === LoanStatus::Approved) {
            $loan->loadMissing('details');
            $assetIds = $loan->details->pluck('aset_id')->filter()->unique()->values();
            if ($assetIds->isEmpty() && $loan->aset_id) {
                $assetIds = collect([(int) $loan->aset_id]);
            }

            $assets = Asset::query()
                ->with('conditionStocks')
                ->whereIn('id', $assetIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($assetIds as $assetId) {
                $this->ensureAssetCanBeBorrowed(
                    $assets->get($assetId),
                    $loan->tanggal_pinjam->toDateString(),
                    $loan->tanggal_kembali->toDateString(),
                    $loan->id,
                );
            }
        }

        $loan->update([
            'status' => $decision,
            'alasan_penolakan' => $decision === LoanStatus::Rejected
                ? $request->validated('alasan_penolakan')
                : null,
        ]);

        app(RiwayatPersetujuanService::class)->record(
            'loan',
            $loan,
            'persetujuan_kasubbag',
            $decision === LoanStatus::Approved ? 'disetujui' : 'ditolak',
            $decision === LoanStatus::Rejected ? $request->validated('alasan_penolakan') : null,
            $request->user(),
        );
    }

    public function handOver(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasPermission('peminjaman.handover'),
            403,
            'Anda tidak memiliki hak akses untuk menyerahkan barang.',
        );

        DB::transaction(function () use ($request, $loanRequest): void {
            $loan = LoanRequest::query()->lockForUpdate()->findOrFail($loanRequest->id);

            if (! $loan->status->isApprovedForHandover()) {
                throw ValidationException::withMessages(['status' => 'Hanya pengajuan yang disetujui yang dapat diserahkan.']);
            }

            $loan->update([
                'status' => LoanStatus::Borrowed,
                'diserahkan_oleh' => $request->user()->id,
                'tanggal_penyerahan' => now(),
            ]);

            app(RiwayatPersetujuanService::class)->record(
                'loan',
                $loan,
                'serah_terima',
                'diserahkan',
                null,
                $request->user(),
            );
        });

        return back()->with('success', 'Barang berhasil diserahkan.');
    }

    public function receiveReturn(ReturnLoanRequest $request, LoanRequest $loanRequest, AssetConditionStockService $conditionStockService): RedirectResponse
    {
        abort_unless(
            $request->user()?->hasPermission('peminjaman.return'),
            403,
            'Anda tidak memiliki hak akses untuk mencatat pengembalian barang.',
        );

        DB::transaction(function () use ($request, $loanRequest, $conditionStockService): void {
            $loan = LoanRequest::query()->lockForUpdate()->findOrFail($loanRequest->id);
            $user = $request->user();

            if ($loan->status !== LoanStatus::Borrowed) {
                throw ValidationException::withMessages(['status' => 'Hanya barang yang sedang dipinjam yang dapat dikembalikan.']);
            }

            $loan->loadMissing('details');
            $details = $loan->details;
            if ($details->isEmpty() && $loan->aset_id) {
                $details = collect([
                    DetailPeminjaman::query()->firstOrCreate([
                        'peminjaman_id' => $loan->id,
                        'aset_id' => $loan->aset_id,
                    ]),
                ]);
            }

            $validated = $request->validated();
            $returnDetails = [];
            foreach ($details as $detail) {
                $detailData = $validated['details'][$detail->id] ?? null;
                if (! $detailData) {
                    throw ValidationException::withMessages(['details' => 'Kondisi pengembalian semua barang wajib diisi.']);
                }

                $returnDetails[$detail->id] = $detailData;
            }

            $assetIds = $details->pluck('aset_id')->filter()->unique()->sort()->values();
            $assets = Asset::query()
                ->whereIn('id', $assetIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($assets->count() !== $assetIds->count()) {
                throw ValidationException::withMessages(['details' => 'Aset pada detail peminjaman tidak ditemukan.']);
            }

            foreach ($details->sortBy('aset_id') as $detail) {
                $detailData = $returnDetails[$detail->id];
                $returnCondition = $detailData['kondisi_kembali'];

                if ($returnCondition !== AssetCondition::Good->value) {
                    $conditionStockService->transfer(
                        $assets->get($detail->aset_id),
                        AssetCondition::Good->value,
                        $returnCondition,
                        1,
                        sprintf(
                            'Pengembalian peminjaman #%d: %s -> %s.',
                            $loan->id,
                            AssetCondition::Good->value,
                            $returnCondition,
                        ),
                        $user->id,
                    );
                }

                $detail->update([
                    'kondisi_kembali' => $returnCondition,
                    'catatan_pengembalian' => $detailData['catatan_pengembalian'] ?? null,
                ]);
            }

            $firstDetailData = $validated['details'][$details->first()->id] ?? [];

            $loan->update([
                'status' => LoanStatus::Returned,
                'diterima_kembali_oleh' => $user->id,
                'tanggal_pengembalian' => now(),
                // Legacy compatibility untuk kolom lama di header.
                'kondisi_kembali' => $firstDetailData['kondisi_kembali'] ?? null,
                'catatan_pengembalian' => $firstDetailData['catatan_pengembalian'] ?? null,
            ]);

            app(RiwayatPersetujuanService::class)->record(
                'loan',
                $loan,
                'pengembalian',
                'dikembalikan',
                null,
                $user,
            );
        });

        return back()->with('success', 'Pengembalian barang berhasil dicatat.');
    }

    public function destroy(Request $request, LoanRequest $loanRequest): RedirectResponse
    {
        abort_if(
            $this->roleValue($request->user()?->role) !== UserRole::SuperAdmin->value,
            403,
            'Pengajuan peminjaman hanya dapat dihapus oleh Super Admin.',
        );

        $loanRequest->delete();

        return back()->with('success', 'Pengajuan peminjaman berhasil dihapus.');
    }

    /** @return list<array{key: string, nama_aset: string, merek_tipe: ?string, available_count: int}> */
    private function borrowableAssetGroups(): array
    {
        $today = today()->toDateString();

        return $this->borrowableAssetBaseQuery()
            ->with('conditionStocks')
            ->orderBy('nama_aset')
            ->get()
            ->reject(fn (Asset $asset): bool => $this->assetHasReservation($asset, $today, $today))
            ->groupBy(fn (Asset $asset): string => $this->assetGroupKey((string) $asset->nama_aset, $asset->merek_tipe))
            ->map(function (Collection $assets, string $key): array {
                $first = $assets->first();

                return [
                    'key' => $key,
                    'nama_aset' => (string) $first->nama_aset,
                    'merek_tipe' => filled($first->merek_tipe) ? (string) $first->merek_tipe : null,
                    'available_count' => $assets->count(),
                ];
            })
            ->sortBy([
                ['nama_aset', 'asc'],
                ['merek_tipe', 'asc'],
            ])
            ->values()
            ->all();
    }

    /** @return Collection<int, Asset> */
    private function allocateAssetsForGroup(string $groupKey, int $quantity, string $borrowDate, string $returnDate, array $excludedAssetIds = []): Collection
    {
        $group = $this->decodeAssetGroupKey($groupKey);

        if (! $group || $quantity < 1) {
            throw ValidationException::withMessages(['items' => 'Barang yang dipilih tidak valid atau sudah tidak tersedia.']);
        }

        $query = $this->borrowableAssetBaseQuery()
            ->with('conditionStocks')
            ->where('nama_aset', $group['nama_aset'])
            ->lockForUpdate();

        if ($group['merek_tipe'] === null) {
            $query->where(fn (Builder $query) => $query
                ->whereNull('merek_tipe')
                ->orWhere('merek_tipe', ''));
        } else {
            $query->where('merek_tipe', $group['merek_tipe']);
        }

        if ($excludedAssetIds !== []) {
            $query->whereNotIn('id', $excludedAssetIds);
        }

        $candidates = $query->get()->sortBy(fn (Asset $asset): string => $this->internalCodeSortValue($asset->kode_internal));
        $allocated = collect();

        foreach ($candidates as $candidate) {
            try {
                $this->ensureAssetCanBeBorrowed($candidate, $borrowDate, $returnDate);

                $allocated->push($candidate);

                if ($allocated->count() >= $quantity) {
                    return $allocated;
                }
            } catch (ValidationException) {
                continue;
            }
        }

        $groupLabel = $group['nama_aset'].($group['merek_tipe'] ? ' - '.$group['merek_tipe'] : '');

        throw ValidationException::withMessages([
            'items' => 'Unit '.$groupLabel.' yang tersedia tidak mencukupi untuk periode peminjaman yang dipilih.',
        ]);
    }

    private function borrowableAssetBaseQuery(): Builder
    {
        return Asset::query()
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui')
            ->whereHas('conditionStocks', fn (Builder $query) => $query
                ->where('kondisi', AssetCondition::Good->value)
                ->where('jumlah', '>', 0))
            ->whereDoesntHave('maintenances', fn (Builder $query) => $query
                ->whereIn('status', $this->activeMaintenanceStatuses()));
    }

    private function ensureAssetCanBeBorrowed(?Asset $asset, string $borrowDate, string $returnDate, ?int $ignoreLoanId = null): void
    {
        if (! $asset) {
            throw ValidationException::withMessages(['items' => 'Data barang tidak ditemukan.']);
        }

        if ($asset->tanggal_penghapusan) {
            throw ValidationException::withMessages(['items' => "Aset {$asset->display_code} - {$asset->nama_aset} telah dinonaktifkan dan tidak dapat dipinjam."]);
        }

        if (($asset->status_verifikasi ?? 'disetujui') !== 'disetujui') {
            throw ValidationException::withMessages(['items' => "Aset {$asset->display_code} - {$asset->nama_aset} belum disetujui oleh Tim Aset."]);
        }

        $asset->loadMissing('conditionStocks');
        $goodStock = (int) ($asset->condition_stock_summary[AssetCondition::Good->value] ?? 0);

        if ($goodStock < 1) {
            throw ValidationException::withMessages(['items' => "Aset {$asset->display_code} - {$asset->nama_aset} tidak dalam kondisi tersedia untuk dipinjam."]);
        }

        if ($this->assetHasActiveMaintenance($asset)) {
            throw ValidationException::withMessages(['items' => "Aset {$asset->display_code} - {$asset->nama_aset} sedang dalam proses pemeliharaan."]);
        }

        if ($this->assetHasReservation($asset, $borrowDate, $returnDate, $ignoreLoanId)) {
            throw ValidationException::withMessages(['items' => "Aset {$asset->display_code} - {$asset->nama_aset} sudah tidak tersedia pada periode peminjaman yang dipilih."]);
        }
    }

    private function assetHasReservation(Asset $asset, string $borrowDate, string $returnDate, ?int $ignoreLoanId = null): bool
    {
        return DetailPeminjaman::query()
            ->where('aset_id', $asset->id)
            ->whereHas('loanRequest', function (Builder $query) use ($borrowDate, $returnDate, $ignoreLoanId): void {
                $query
                    ->whereIn('status', $this->reservedLoanStatuses())
                    ->whereDate('tanggal_pinjam', '<=', $returnDate)
                    ->whereDate('tanggal_kembali', '>=', $borrowDate);

                if ($ignoreLoanId) {
                    $query->whereKeyNot($ignoreLoanId);
                }
            })
            ->exists();
    }

    private function assetHasActiveMaintenance(Asset $asset): bool
    {
        return $asset->maintenances()
            ->whereIn('status', $this->activeMaintenanceStatuses())
            ->exists();
    }

    /** @return list<string> */
    private function reservedLoanStatuses(): array
    {
        return [
            LoanStatus::Submitted->value,
            LoanStatus::VerifiedByAssetTeam->value,
            LoanStatus::Approved->value,
            LoanStatus::LegacyApproved->value,
            LoanStatus::Borrowed->value,
        ];
    }

    /** @return list<string> */
    private function activeMaintenanceStatuses(): array
    {
        return [
            AssetMaintenanceStatus::Submitted->value,
            AssetMaintenanceStatus::Approved->value,
            AssetMaintenanceStatus::InProgress->value,
        ];
    }

    private function assetGroupKey(string $assetName, ?string $brandType): string
    {
        $payload = json_encode([
            'nama_aset' => trim($assetName),
            'merek_tipe' => filled($brandType) ? trim((string) $brandType) : null,
        ]);

        return rtrim(strtr(base64_encode((string) $payload), '+/', '-_'), '=');
    }

    /** @return array{nama_aset: string, merek_tipe: ?string}|null */
    private function decodeAssetGroupKey(string $key): ?array
    {
        $decoded = base64_decode(strtr($key, '-_', '+/'), true);
        $payload = is_string($decoded) ? json_decode($decoded, true) : null;

        if (! is_array($payload) || blank($payload['nama_aset'] ?? null)) {
            return null;
        }

        return [
            'nama_aset' => trim((string) $payload['nama_aset']),
            'merek_tipe' => filled($payload['merek_tipe'] ?? null) ? trim((string) $payload['merek_tipe']) : null,
        ];
    }

    private function internalCodeSortValue(?string $code): string
    {
        if (preg_match('/^(.+?)\/(\d+)$/', (string) $code, $matches)) {
            return $matches[1].'/'.str_pad((string) ((int) $matches[2]), 12, '0', STR_PAD_LEFT);
        }

        return (string) $code;
    }

    private function authorizeView(Request $request, LoanRequest $loanRequest): void
    {
        $role = $this->roleValue($request->user()?->role);

        abort_if(
            $role === UserRole::AdminBidang->value && $loanRequest->pemohon_id !== $request->user()->id,
            403,
            'Anda tidak memiliki akses ke pengajuan ini.',
        );
    }

    private function roleValue(mixed $role): ?string
    {
        return $role instanceof UserRole ? $role->value : $role;
    }
}
