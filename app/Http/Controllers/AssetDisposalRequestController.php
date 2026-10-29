<?php

namespace App\Http\Controllers;

use App\Enums\AssetDisposalStatus;
use App\Enums\AssetCondition;
use App\Enums\AssetMaintenanceStatus;
use App\Enums\LoanStatus;
use App\Http\Requests\DecideAssetDisposalRequest;
use App\Http\Requests\StoreAssetDisposalRequest;
use App\Models\Asset;
use App\Models\AssetConditionMutation;
use App\Models\AssetDisposalEvidence;
use App\Models\AssetDisposalRequest;
use App\Models\AssetMaintenance;
use App\Models\DetailPeminjaman;
use App\Models\LoanRequest;
use App\Services\RiwayatPersetujuanService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AssetDisposalRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeView($request);

        $status = AssetDisposalStatus::tryFrom($request->string('status')->toString());
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $disposals = AssetDisposalRequest::query()
            ->with($this->relations())
            ->when($status, fn (Builder $query) => $query->where('status', $status->value))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $eligibleAssets = Asset::query()
            ->with(['category', 'conditionStocks'])
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui')
            ->where('jumlah', 1)
            ->where('kondisi', AssetCondition::HeavyDamage->value)
            ->whereHas('conditionMutations', fn (Builder $query) => $query
                ->where('ke_kondisi', AssetCondition::HeavyDamage->value)
                ->whereHas('documents', fn (Builder $documentQuery) => $documentQuery->where('jenis', 'foto')))
            ->whereDoesntHave('disposalRequests', fn (Builder $query) => $query->where('status', AssetDisposalStatus::Submitted->value))
            ->whereDoesntHave('loanDetails.loanRequest', fn (Builder $query) => $query->whereIn('status', $this->blockingLoanStatuses()))
            ->whereDoesntHave('loanRequests', fn (Builder $query) => $query->whereIn('status', $this->blockingLoanStatuses()))
            ->whereDoesntHave('maintenances', fn (Builder $query) => $query->whereIn('status', $this->blockingMaintenanceStatuses()))
            ->orderBy('nama_aset')
            ->get();

        return view('penghapusan-aset.index', [
            'disposals' => $disposals,
            'eligibleAssets' => $eligibleAssets,
            'statuses' => AssetDisposalStatus::cases(),
            'reasons' => \App\Enums\AssetDisposalReason::cases(),
        ]);
    }

    public function store(StoreAssetDisposalRequest $request): RedirectResponse
    {
        if (! $this->hasEvidenceSchema()) {
            throw ValidationException::withMessages([
                'aset_id' => 'Struktur database bukti penghapusan belum tersedia. Jalankan migration fitur penghapusan terlebih dahulu.',
            ]);
        }

        $document = $request->file('document');
        $supportPath = null;
        $storedPaths = [];
        $evidencePayloads = [];

        try {
            $asset = Asset::query()
                ->with(['conditionStocks'])
                ->findOrFail($request->integer('aset_id'));
            $mutation = $this->eligibleHeavyDamageMutation($asset);

            if ($document) {
                $supportPath = $document->store('asset-disposals', 'local');
                $storedPaths[] = $supportPath;
            }

            $evidencePayloads = $this->copyConditionEvidence($mutation, $storedPaths);

            DB::transaction(function () use ($request, $asset, $mutation, $document, $supportPath, $evidencePayloads): void {
                $lockedAsset = Asset::query()->lockForUpdate()->findOrFail($asset->id);
                $lockedMutation = $this->eligibleHeavyDamageMutation($lockedAsset);

                if ($lockedMutation->id !== $mutation->id) {
                    throw ValidationException::withMessages([
                        'aset_id' => 'Bukti perubahan kondisi aset berubah. Silakan pilih ulang aset.',
                    ]);
                }

                if (AssetDisposalRequest::query()
                    ->where('aset_id', $lockedAsset->id)
                    ->where('status', AssetDisposalStatus::Submitted->value)
                    ->lockForUpdate()
                    ->exists()) {
                    throw ValidationException::withMessages([
                        'aset_id' => 'Aset ini masih memiliki pengajuan penghapusan aktif.',
                    ]);
                }

                $this->ensureAssetHasNoBlockingProcess($lockedAsset);

                $disposal = AssetDisposalRequest::query()->create([
                    'aset_id' => $lockedAsset->id,
                    'asset_condition_mutation_id' => $lockedMutation->id,
                    'pengusul_id' => $request->user()->id,
                    'alasan' => \App\Enums\AssetDisposalReason::HeavilyDamaged,
                    'catatan' => $request->validated('catatan'),
                    'catatan_kondisi_snapshot' => $lockedMutation->catatan,
                    'google_drive_url_snapshot' => $lockedMutation->google_drive_url,
                    'path_dokumen' => $supportPath,
                    'nama_dokumen' => $document?->getClientOriginalName(),
                    'mime_dokumen' => $document?->getClientMimeType(),
                    'status' => AssetDisposalStatus::Submitted,
                ]);

                $disposal->evidences()->createMany($evidencePayloads);

                app(RiwayatPersetujuanService::class)->record('disposal', $disposal, 'pengajuan', 'dibuat', $disposal->catatan, $request->user());
            });

        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $exception;
        }

        return back()->with('success', 'Pengajuan penghapusan aset berhasil dikirim.');
    }

    public function show(AssetDisposalRequest $assetDisposalRequest): View
    {
        request()->merge([]);
        $this->authorizeView(request());

        return view('penghapusan-aset.show', [
            'disposal' => $assetDisposalRequest->load($this->relations()),
        ]);
    }

    public function assetEvidence(Request $request, Asset $asset): JsonResponse
    {
        $this->authorizeView($request);

        $asset->load(['category', 'conditionStocks']);
        $mutation = $this->eligibleHeavyDamageMutation($asset);

        return response()->json([
            'asset' => [
                'id' => $asset->id,
                'kode_internal' => $asset->display_code,
                'nama_aset' => $asset->nama_aset,
                'kategori' => $asset->category?->nama_kategori ?: '-',
                'kondisi' => 'Rusak Berat (RB)',
            ],
            'mutation' => [
                'id' => $mutation->id,
                'catatan' => $mutation->catatan ?: '',
                'tanggal' => $mutation->created_at?->translatedFormat('d M Y H:i') ?: '-',
                'petugas' => $mutation->user?->name ?: '-',
                'google_drive_url' => $mutation->google_drive_url,
            ],
            'documents' => $mutation->documents->map(fn ($document): array => [
                'id' => $document->id,
                'jenis' => $document->jenis,
                'nama_file' => $document->nama_file_asli ?: basename((string) $document->path_file),
                'mime_type' => $document->mime_type,
                'url' => Storage::disk('public')->exists($document->path_file)
                    ? Storage::disk('public')->url($document->path_file)
                    : null,
                'available' => Storage::disk('public')->exists($document->path_file),
            ])->values(),
        ]);
    }

    public function decide(
        DecideAssetDisposalRequest $request,
        AssetDisposalRequest $assetDisposalRequest,
    ): RedirectResponse {
        $decision = AssetDisposalStatus::from($request->validated('decision'));

        DB::transaction(function () use ($request, $assetDisposalRequest, $decision): void {
            $disposal = AssetDisposalRequest::query()->lockForUpdate()->findOrFail($assetDisposalRequest->id);

            if ($disposal->status !== AssetDisposalStatus::Submitted) {
                throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
            }

            if ($decision === AssetDisposalStatus::Approved) {
                $asset = Asset::query()->lockForUpdate()->findOrFail($disposal->aset_id);

                if ($asset->tanggal_penghapusan) {
                    throw ValidationException::withMessages(['aset_id' => 'Aset ini sudah dihapus.']);
                }

                $this->ensureAssetHasNoBlockingProcess($asset);

                $asset->update(['tanggal_penghapusan' => now()]);
            }

            $disposal->update([
                'status' => $decision,
                'diperiksa_oleh' => $request->user()->id,
                'tanggal_pemeriksaan' => now(),
                'alasan_penolakan' => $decision === AssetDisposalStatus::Rejected
                    ? $request->validated('alasan_penolakan')
                    : null,
            ]);

            app(RiwayatPersetujuanService::class)->record(
                'disposal',
                $disposal,
                'kasubbag',
                $decision === AssetDisposalStatus::Approved ? 'disetujui' : 'ditolak',
                $decision === AssetDisposalStatus::Rejected ? $request->validated('alasan_penolakan') : null,
                $request->user(),
            );
        });

        return back()->with('success', $decision === AssetDisposalStatus::Approved ? 'Penghapusan aset disetujui.' : 'Penghapusan aset ditolak.');
    }

    public function downloadDocument(AssetDisposalRequest $assetDisposalRequest): StreamedResponse
    {
        $this->authorizeView(request());

        $this->ensureDocumentExists($assetDisposalRequest);

        return Storage::disk('local')->download(
            $assetDisposalRequest->path_dokumen,
            $assetDisposalRequest->nama_dokumen,
            ['Content-Type' => $assetDisposalRequest->mime_dokumen],
        );
    }

    public function previewDocument(AssetDisposalRequest $assetDisposalRequest): Response
    {
        $this->authorizeView(request());

        $this->ensureDocumentExists($assetDisposalRequest);

        return response(
            Storage::disk('local')->get($assetDisposalRequest->path_dokumen),
            Response::HTTP_OK,
            [
                'Content-Type' => $assetDisposalRequest->mime_dokumen ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $assetDisposalRequest->nama_dokumen ?: 'dokumen-penghapusan').'"',
            ],
        );
    }

    public function downloadEvidence(Request $request, AssetDisposalEvidence $evidence): StreamedResponse
    {
        $this->authorizeView($request);

        abort_unless(
            $evidence->disposalRequest && Storage::disk('local')->exists($evidence->path_file),
            JsonResponse::HTTP_NOT_FOUND,
            'Bukti kondisi tidak ditemukan.',
        );

        return Storage::disk('local')->download(
            $evidence->path_file,
            $evidence->nama_file ?: 'bukti-kondisi',
            ['Content-Type' => $evidence->mime_type ?: 'application/octet-stream'],
        );
    }

    public function previewEvidence(Request $request, AssetDisposalEvidence $evidence): Response
    {
        $this->authorizeView($request);

        abort_unless(
            $evidence->disposalRequest && Storage::disk('local')->exists($evidence->path_file),
            JsonResponse::HTTP_NOT_FOUND,
            'Bukti kondisi tidak ditemukan.',
        );

        return response(
            Storage::disk('local')->get($evidence->path_file),
            Response::HTTP_OK,
            [
                'Content-Type' => $evidence->mime_type ?: 'application/octet-stream',
                'Content-Disposition' => 'inline; filename="'.str_replace('"', '', $evidence->nama_file ?: 'bukti-kondisi').'"',
            ],
        );
    }

    private function ensureDocumentExists(AssetDisposalRequest $assetDisposalRequest): void
    {
        abort_unless(
            $assetDisposalRequest->path_dokumen && Storage::disk('local')->exists($assetDisposalRequest->path_dokumen),
            JsonResponse::HTTP_NOT_FOUND,
            'Dokumen pendukung tidak ditemukan.',
        );
    }

    private function authorizeView(Request $request): void
    {
        abort_unless(
            $request->user()?->hasPermission('penghapusan.view'),
            403,
            'Anda tidak memiliki akses ke penghapusan aset.',
        );
    }

    private function eligibleHeavyDamageMutation(Asset $asset): AssetConditionMutation
    {
        if ($asset->tanggal_penghapusan) {
            throw ValidationException::withMessages(['aset_id' => 'Aset ini sudah dihapus.']);
        }

        if ((int) $asset->jumlah !== 1) {
            throw ValidationException::withMessages(['aset_id' => 'Aset legacy dengan jumlah lebih dari 1 harus diperbaiki terlebih dahulu sebelum diajukan penghapusan.']);
        }

        if (($asset->status_verifikasi ?? 'disetujui') !== 'disetujui') {
            throw ValidationException::withMessages(['aset_id' => 'Aset belum disetujui oleh Tim Aset.']);
        }

        if ($asset->kondisi !== AssetCondition::HeavyDamage) {
            throw ValidationException::withMessages(['aset_id' => 'Penghapusan hanya dapat diajukan untuk aset dengan kondisi Rusak Berat.']);
        }

        $asset->loadMissing('conditionStocks');
        $hasHeavyDamageStock = (int) ($asset->condition_stock_summary[AssetCondition::HeavyDamage->value] ?? 0) === 1;

        if (! $hasHeavyDamageStock) {
            throw ValidationException::withMessages(['aset_id' => 'Stok kondisi aset belum sinkron sebagai Rusak Berat. Perbaiki data kondisi terlebih dahulu.']);
        }

        $this->ensureAssetHasNoBlockingProcess($asset);

        $mutation = $asset->conditionMutations()
            ->with(['documents', 'user'])
            ->where('ke_kondisi', AssetCondition::HeavyDamage->value)
            ->latest('created_at')
            ->latest('id')
            ->first();

        if (! $mutation) {
            throw ValidationException::withMessages(['aset_id' => 'Riwayat perubahan kondisi Rusak Berat belum tersedia.']);
        }

        $laterDifferentMutationExists = $asset->conditionMutations()
            ->where(function (Builder $query) use ($mutation): void {
                $query->where('created_at', '>', $mutation->created_at)
                    ->orWhere(function (Builder $query) use ($mutation): void {
                        $query->where('created_at', $mutation->created_at)
                            ->where('id', '>', $mutation->id);
                    });
            })
            ->where('ke_kondisi', '!=', AssetCondition::HeavyDamage->value)
            ->exists();

        if ($laterDifferentMutationExists) {
            throw ValidationException::withMessages(['aset_id' => 'Riwayat Rusak Berat tidak lagi menjadi kondisi terbaru aset.']);
        }

        $hasPhoto = $mutation->documents
            ->where('jenis', 'foto')
            ->contains(fn ($document): bool => Storage::disk('public')->exists($document->path_file));

        if (! $hasPhoto) {
            throw ValidationException::withMessages(['aset_id' => 'Dokumentasi foto perubahan kondisi Rusak Berat belum tersedia.']);
        }

        return $mutation;
    }

    private function ensureAssetHasNoBlockingProcess(Asset $asset): void
    {
        $hasLegacyLoan = LoanRequest::query()
            ->where('aset_id', $asset->id)
            ->whereIn('status', $this->blockingLoanStatuses())
            ->exists();

        $hasDetailLoan = DetailPeminjaman::query()
            ->where('aset_id', $asset->id)
            ->whereHas('loanRequest', fn (Builder $query) => $query->whereIn('status', $this->blockingLoanStatuses()))
            ->exists();

        if ($hasLegacyLoan || $hasDetailLoan) {
            throw ValidationException::withMessages(['aset_id' => 'Aset masih direservasi atau sedang dipinjam.']);
        }

        $hasActiveMaintenance = AssetMaintenance::query()
            ->where('asset_id', $asset->id)
            ->whereIn('status', $this->blockingMaintenanceStatuses())
            ->exists();

        if ($hasActiveMaintenance) {
            throw ValidationException::withMessages(['aset_id' => 'Aset masih memiliki proses pemeliharaan aktif.']);
        }
    }

    /** @return list<string> */
    private function blockingLoanStatuses(): array
    {
        return [
            LoanStatus::Approved->value,
            LoanStatus::LegacyApproved->value,
            LoanStatus::Borrowed->value,
        ];
    }

    /** @return list<string> */
    private function blockingMaintenanceStatuses(): array
    {
        return [
            AssetMaintenanceStatus::Submitted->value,
            AssetMaintenanceStatus::Approved->value,
            AssetMaintenanceStatus::InProgress->value,
        ];
    }

    /**
     * @param  list<string>  $storedPaths
     * @return list<array<string, mixed>>
     */
    private function copyConditionEvidence(AssetConditionMutation $mutation, array &$storedPaths): array
    {
        $payloads = [];
        $batch = (string) Str::uuid();

        foreach ($mutation->documents as $document) {
            if (! Storage::disk('public')->exists($document->path_file)) {
                continue;
            }

            $extension = pathinfo((string) ($document->nama_file_asli ?: $document->path_file), PATHINFO_EXTENSION);
            $filename = Str::uuid()->toString().($extension ? '.'.$extension : '');
            $targetPath = "asset-disposals/evidence/{$batch}/{$filename}";

            Storage::disk('local')->put($targetPath, Storage::disk('public')->get($document->path_file));
            $storedPaths[] = $targetPath;

            $payloads[] = [
                'asset_condition_mutation_document_id' => $document->id,
                'jenis' => $document->jenis,
                'path_file' => $targetPath,
                'nama_file' => $document->nama_file_asli ?: basename((string) $document->path_file),
                'mime_type' => $document->mime_type,
                'ukuran_file' => $document->ukuran_file,
            ];
        }

        if (! collect($payloads)->contains(fn (array $payload): bool => $payload['jenis'] === 'foto')) {
            throw ValidationException::withMessages(['aset_id' => 'Dokumentasi foto perubahan kondisi Rusak Berat belum tersedia.']);
        }

        return $payloads;
    }

    /** @return list<string> */
    private function relations(): array
    {
        $relations = ['requester', 'asset.category', 'reviewer'];

        if (Schema::hasColumn('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')) {
            $relations[] = 'conditionMutation.user';
        }

        if (Schema::hasTable('pengajuan_penghapusan_bukti')) {
            $relations[] = 'evidences';
        }

        return $relations;
    }

    private function hasEvidenceSchema(): bool
    {
        return Schema::hasColumn('pengajuan_penghapusan_aset', 'asset_condition_mutation_id')
            && Schema::hasColumn('pengajuan_penghapusan_aset', 'catatan_kondisi_snapshot')
            && Schema::hasColumn('pengajuan_penghapusan_aset', 'google_drive_url_snapshot')
            && Schema::hasTable('pengajuan_penghapusan_bukti');
    }
}
