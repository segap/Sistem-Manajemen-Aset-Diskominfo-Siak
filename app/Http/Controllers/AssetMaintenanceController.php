<?php

namespace App\Http\Controllers;

use App\Enums\AssetCondition;
use App\Enums\AssetMaintenanceStatus;
use App\Http\Requests\Maintenance\CompleteAssetMaintenanceRequest;
use App\Http\Requests\Maintenance\StoreAssetMaintenanceRequest;
use App\Http\Requests\Maintenance\UpdateAssetMaintenanceRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceReportDataRequest;
use App\Http\Requests\Maintenance\VerifyAssetMaintenanceRequest;
use App\Models\Asset;
use App\Models\AssetMaintenance;
use App\Models\AssetMaintenanceDocument;
use App\Services\AssetConditionStockService;
use App\Services\MaintenanceReportWordService;
use App\Services\RiwayatPersetujuanService;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetMaintenanceController extends Controller
{
    private const HISTORY_TYPE = 'maintenance';
    private const ACTIVE_STATUSES = [
        AssetMaintenanceStatus::Submitted->value,
        AssetMaintenanceStatus::Approved->value,
        AssetMaintenanceStatus::InProgress->value,
    ];

    public function index(Request $request): View
    {
        $role = $request->user()?->role;
        $isApplicant = $role === 'admin_bidang';
        $perPage = min(max($request->integer('per_page', 10), 1), 100);

        $maintenances = AssetMaintenance::query()
            ->with(['asset.category', 'asset.kodeBarang', 'applicant', 'verifier', 'completedBy', 'documents'])
            ->when($isApplicant, fn (Builder $query) => $query->where('pemohon_id', $request->user()->id))
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(fn (Builder $query) => $query
                    ->whereHas('asset', fn (Builder $query) => $query
                        ->where('kode_internal', 'like', "%{$search}%")
                        ->orWhere('nama_aset', 'like', "%{$search}%"))
                    ->orWhereHas('applicant', fn (Builder $query) => $query->where('name', 'like', "%{$search}%")));
            })
            ->when($request->filled('kondisi'), fn (Builder $query) => $query->where('kondisi_sebelum', $request->string('kondisi')))
            ->when($request->filled('prioritas'), fn (Builder $query) => $query->where('prioritas', $request->string('prioritas')))
            ->when($request->filled('status'), fn (Builder $query) => $query->where('status', $request->string('status')))
            ->orderByRaw("CASE WHEN status = 'selesai' THEN 1 ELSE 0 END")
            ->orderByRaw("CASE prioritas WHEN 'tinggi' THEN 1 WHEN 'sedang' THEN 2 WHEN 'rendah' THEN 3 ELSE 4 END")
            ->oldest('tanggal_pengajuan')
            ->paginate($perPage)
            ->withQueryString();

        return view('pemeliharaan-aset.index', [
            'maintenances' => $maintenances,
            'maintenanceOptions' => $this->maintenanceOptions(),
            'histories' => $maintenances->getCollection()
                ->mapWithKeys(fn (AssetMaintenance $maintenance) => [$maintenance->id => app(RiwayatPersetujuanService::class)->timeline(self::HISTORY_TYPE, $maintenance)]),
        ]);
    }

    public function store(StoreAssetMaintenanceRequest $request): RedirectResponse
    {
        [$assetId, $condition] = $this->parseMaintenanceUnit($request->string('maintenance_unit')->toString());

        $photoPath = $request->hasFile('foto_sebelum')
            ? $request->file('foto_sebelum')->store('asset-maintenances/photos', 'public')
            : null;
        $videoPath = $request->hasFile('video_sebelum')
            ? $request->file('video_sebelum')->store('asset-maintenances/videos', 'public')
            : null;

        try {
            $maintenance = DB::transaction(function () use ($request, $assetId, $condition, $photoPath, $videoPath): AssetMaintenance {
                $asset = Asset::query()->with('conditionStocks')->lockForUpdate()->findOrFail($assetId);
                $available = $this->availableMaintenanceStock($asset, $condition);

                if ($available < 1) {
                    throw ValidationException::withMessages(['maintenance_unit' => 'Unit aset untuk kondisi ini sudah habis atau sedang dalam proses pemeliharaan aktif.']);
                }

                $maintenance = AssetMaintenance::query()->create([
                    'asset_id' => $asset->id,
                    'pemohon_id' => $request->user()->id,
                    'tanggal_pengajuan' => now(),
                    'kondisi_sebelum' => $condition,
                    'jumlah_dipelihara' => 1,
                    'keluhan' => $request->string('keluhan'),
                    'catatan_pengajuan' => $request->input('catatan_pengajuan'),
                    'foto_sebelum' => $photoPath,
                    'link_drive_dokumentasi' => $request->input('link_drive_dokumentasi'),
                    'status' => AssetMaintenanceStatus::Submitted->value,
                ]);

                if ($photoPath) {
                    $this->addDocument($maintenance, 'foto_sebelum', $photoPath, $request->file('foto_sebelum')?->getClientOriginalName(), $request->user()->id);
                }
                if ($videoPath) {
                    $this->addDocument($maintenance, 'video_sebelum', $videoPath, $request->file('video_sebelum')?->getClientOriginalName(), $request->user()->id);
                }

                app(RiwayatPersetujuanService::class)->record(self::HISTORY_TYPE, $maintenance, 'pengajuan', 'dibuat', $maintenance->keluhan, $request->user());

                return $maintenance;
            });
        } catch (\Throwable $exception) {
            foreach (array_filter([$photoPath, $videoPath]) as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return back()->with('success', 'Pengajuan pemeliharaan berhasil dibuat dan menunggu verifikasi Tim Aset.');
    }

    public function verify(VerifyAssetMaintenanceRequest $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        if ($assetMaintenance->status !== AssetMaintenanceStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'Pengajuan ini sudah diproses.']);
        }

        $decision = $request->string('decision')->toString();
        $status = $decision === 'approve' ? AssetMaintenanceStatus::Approved : AssetMaintenanceStatus::Rejected;

        DB::transaction(function () use ($request, $assetMaintenance, $status): void {
            $assetMaintenance->update([
                'status' => $status->value,
                'prioritas' => $status === AssetMaintenanceStatus::Approved ? $request->input('prioritas') : null,
                'diverifikasi_oleh' => $request->user()->id,
                'diverifikasi_pada' => now(),
                'catatan_verifikasi' => $request->input('catatan_verifikasi'),
            ]);

            app(RiwayatPersetujuanService::class)->record(
                self::HISTORY_TYPE,
                $assetMaintenance,
                'verifikasi_tim_aset',
                $status === AssetMaintenanceStatus::Approved ? 'disetujui' : 'ditolak',
                $request->input('catatan_verifikasi'),
                $request->user(),
            );
        });

        return back()->with('success', $status === AssetMaintenanceStatus::Approved ? 'Pemeliharaan disetujui Tim Aset.' : 'Pemeliharaan ditolak Tim Aset.');
    }

    public function start(Request $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $this->authorizeAssetTeam($request);

        if ($assetMaintenance->status !== AssetMaintenanceStatus::Approved) {
            throw ValidationException::withMessages(['status' => 'Pemeliharaan hanya dapat dimulai setelah disetujui Tim Aset.']);
        }

        $assetMaintenance->update([
            'status' => AssetMaintenanceStatus::InProgress->value,
            'tanggal_mulai' => now(),
        ]);

        app(RiwayatPersetujuanService::class)->record(self::HISTORY_TYPE, $assetMaintenance, 'mulai_pemeliharaan', 'dimulai', null, $request->user());

        return back()->with('success', 'Pemeliharaan aset dimulai.');
    }

    public function update(UpdateAssetMaintenanceRequest $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $this->authorizeAssetTeam($request);

        $paths = [];
        $documentationPaths = [];

        try {
            if ($request->hasFile('foto_sebelum')) {
                $paths['foto_sebelum'] = $request->file('foto_sebelum')->store('asset-maintenances/photos', 'public');
            }
            if ($request->hasFile('foto_setelah')) {
                $paths['foto_setelah'] = $request->file('foto_setelah')->store('asset-maintenances/photos', 'public');
            }
            if ($request->hasFile('invoice')) {
                $paths['invoice'] = $request->file('invoice')->store('asset-maintenances/documents', 'public');
            }
            if ($request->hasFile('video_dokumentasi')) {
                $paths['video_dokumentasi'] = $request->file('video_dokumentasi')->store('asset-maintenances/videos', 'public');
            }
            foreach ($request->file('dokumentasi_servis', []) as $file) {
                $documentationPaths[] = $file->store('asset-maintenances/photos', 'public');
            }

            DB::transaction(function () use ($request, $assetMaintenance, $paths, $documentationPaths): void {
                $data = $request->safe()->except(['foto_sebelum', 'foto_setelah', 'invoice', 'dokumentasi_servis', 'video_dokumentasi']);

                if ($assetMaintenance->status === AssetMaintenanceStatus::Submitted) {
                    $data = collect($data)->only(['keluhan', 'catatan_pengajuan', 'prioritas', 'catatan_verifikasi', 'link_drive_dokumentasi'])->all();
                } elseif ($assetMaintenance->status === AssetMaintenanceStatus::Approved) {
                    $data = collect($data)->only(['keluhan', 'catatan_pengajuan', 'prioritas', 'catatan_verifikasi', 'tanggal_mulai', 'penyedia_servis', 'link_drive_dokumentasi'])->all();
                } elseif ($assetMaintenance->status === AssetMaintenanceStatus::InProgress) {
                    $data = collect($data)->except(['tanggal_selesai'])->all();
                } elseif ($assetMaintenance->status === AssetMaintenanceStatus::Completed) {
                    $data = collect($data)->except(['kondisi_setelah'])->all();
                }

                if (array_key_exists('jenis_pemeliharaan', $data) && $data['jenis_pemeliharaan'] !== 'lainnya') {
                    $data['jenis_pemeliharaan_lainnya'] = null;
                }

                if (isset($paths['foto_sebelum'])) {
                    $data['foto_sebelum'] = $paths['foto_sebelum'];
                }
                if (isset($paths['foto_setelah']) && $assetMaintenance->status !== AssetMaintenanceStatus::Submitted) {
                    $data['foto_setelah'] = $paths['foto_setelah'];
                }

                $before = $assetMaintenance->replicate();
                $assetMaintenance->update($data);

                if (isset($paths['foto_sebelum'])) {
                    $this->addDocument($assetMaintenance, 'foto_sebelum', $paths['foto_sebelum'], $request->file('foto_sebelum')?->getClientOriginalName(), $request->user()->id);
                }
                if (isset($paths['foto_setelah'])) {
                    $this->addDocument($assetMaintenance, 'foto_setelah', $paths['foto_setelah'], $request->file('foto_setelah')?->getClientOriginalName(), $request->user()->id);
                }
                if (isset($paths['invoice'])) {
                    $this->addDocument($assetMaintenance, 'invoice', $paths['invoice'], $request->file('invoice')?->getClientOriginalName(), $request->user()->id);
                }
                if (isset($paths['video_dokumentasi'])) {
                    $this->addDocument($assetMaintenance, 'video_dokumentasi', $paths['video_dokumentasi'], $request->file('video_dokumentasi')?->getClientOriginalName(), $request->user()->id);
                }
                foreach ($documentationPaths as $index => $path) {
                    $file = $request->file('dokumentasi_servis')[$index] ?? null;
                    $this->addDocument($assetMaintenance, 'dokumentasi_servis', $path, $file?->getClientOriginalName(), $request->user()->id);
                }

                $changes = collect($data)
                    ->except(['foto_sebelum', 'foto_setelah'])
                    ->filter(fn ($value, string $field): bool => (string) ($before->{$field} instanceof \BackedEnum ? $before->{$field}->value : $before->{$field}) !== (string) $value)
                    ->keys()
                    ->map(fn (string $field): string => str($field)->replace('_', ' ')->title()->toString())
                    ->values();

                $documentChanges = collect([
                    isset($paths['foto_sebelum']) ? 'Foto Sebelum' : null,
                    isset($paths['foto_setelah']) ? 'Foto Setelah' : null,
                    isset($paths['invoice']) ? 'Invoice/Bukti Servis' : null,
                    isset($paths['video_dokumentasi']) ? 'Video Dokumentasi' : null,
                    $documentationPaths !== [] ? 'Dokumentasi Servis' : null,
                    filled($request->input('link_drive_dokumentasi')) ? 'Link Google Drive' : null,
                ])->filter();

                $summary = $changes->concat($documentChanges)->isNotEmpty()
                    ? 'Data diperbarui: '.$changes->concat($documentChanges)->implode(', ')
                    : 'Data Pemeliharaan diperbarui.';

                app(RiwayatPersetujuanService::class)->record(self::HISTORY_TYPE, $assetMaintenance, 'data_diperbarui', 'diperbarui', $summary, $request->user());
            });
        } catch (\Throwable $exception) {
            foreach (array_filter([...array_values($paths), ...$documentationPaths]) as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return back()->with('success', 'Data pemeliharaan berhasil diperbarui.');
    }

    public function complete(CompleteAssetMaintenanceRequest $request, AssetMaintenance $assetMaintenance, AssetConditionStockService $conditionStockService): RedirectResponse
    {
        if ($assetMaintenance->status !== AssetMaintenanceStatus::InProgress) {
            throw ValidationException::withMessages(['status' => 'Pemeliharaan hanya dapat diselesaikan saat status Sedang Dipelihara.']);
        }

        $paths = [];

        try {
            $paths['foto_setelah'] = $request->hasFile('foto_setelah')
                ? $request->file('foto_setelah')->store('asset-maintenances/photos', 'public')
                : null;
            $paths['invoice'] = $request->hasFile('invoice')
                ? $request->file('invoice')->store('asset-maintenances/documents', 'public')
                : null;
            $paths['video_dokumentasi'] = $request->hasFile('video_dokumentasi')
                ? $request->file('video_dokumentasi')->store('asset-maintenances/videos', 'public')
                : null;
            $documentationPaths = [];
            foreach ($request->file('dokumentasi_servis', []) as $file) {
                $documentationPaths[] = $file->store('asset-maintenances/photos', 'public');
            }

            DB::transaction(function () use ($request, $assetMaintenance, $conditionStockService, $paths, $documentationPaths): void {
                $assetMaintenance->load('asset.conditionStocks');
                $before = $assetMaintenance->kondisi_sebelum->value;
                $after = $request->string('kondisi_setelah')->toString();
                $quantity = max(1, (int) ($assetMaintenance->jumlah_dipelihara ?: 1));

                $assetMaintenance->update([
                    'status' => AssetMaintenanceStatus::Completed->value,
                    'tanggal_selesai' => now(),
                    'penyedia_servis' => $request->input('penyedia_servis'),
                    'jenis_pemeliharaan' => $request->input('jenis_pemeliharaan'),
                    'jenis_pemeliharaan_lainnya' => $request->input('jenis_pemeliharaan_lainnya'),
                    'nama_pelaksana' => $request->input('nama_pelaksana'),
                    'identitas_pelaksana' => $request->input('identitas_pelaksana'),
                    'komponen_material' => $request->input('komponen_material'),
                    'tindakan_servis' => $request->input('tindakan_servis'),
                    'biaya_servis' => $request->input('biaya_servis'),
                    'fungsi_utama' => $request->input('fungsi_utama'),
                    'hasil_pengujian' => $request->input('hasil_pengujian'),
                    'hasil_akhir_pemeliharaan' => $request->input('hasil_akhir_pemeliharaan'),
                    'nomor_berita_acara' => $request->input('nomor_berita_acara'),
                    'tanggal_berita_acara' => $request->input('tanggal_berita_acara'),
                    'lokasi_pelaksanaan' => $request->input('lokasi_pelaksanaan'),
                    'dasar_pelaksanaan' => $request->input('dasar_pelaksanaan'),
                    'tempat_ttd' => $request->input('tempat_ttd'),
                    'jabatan_penanggung_jawab' => $request->input('jabatan_penanggung_jawab'),
                    'nama_penanggung_jawab' => $request->input('nama_penanggung_jawab'),
                    'nip_penanggung_jawab' => $request->input('nip_penanggung_jawab'),
                    'berita_acara_dibuat_pada' => now(),
                    'berita_acara_dibuat_oleh' => $request->user()->id,
                    'kondisi_setelah' => $after,
                    'catatan_hasil' => $request->input('catatan_hasil'),
                    'foto_setelah' => $paths['foto_setelah'],
                    'link_drive_dokumentasi' => $request->input('link_drive_dokumentasi', $assetMaintenance->link_drive_dokumentasi),
                    'selesai_oleh' => $request->user()->id,
                ]);

                if ($quantity > 0 && $before !== $after) {
                    $conditionStockService->transfer($assetMaintenance->asset, $before, $after, $quantity, 'Hasil pemeliharaan #'.$assetMaintenance->id, $request->user()->id, $request->input('link_drive_dokumentasi'));
                } else {
                    $assetMaintenance->asset->update(['kondisi' => $after]);
                }

                if ($after === AssetCondition::Good->value && $paths['foto_setelah']) {
                    $assetMaintenance->asset->update(['path_foto' => $paths['foto_setelah']]);
                }

                if ($paths['invoice']) {
                    $this->addDocument($assetMaintenance, 'invoice', $paths['invoice'], $request->file('invoice')?->getClientOriginalName(), $request->user()->id);
                }
                if ($paths['video_dokumentasi']) {
                    $this->addDocument($assetMaintenance, 'video_dokumentasi', $paths['video_dokumentasi'], $request->file('video_dokumentasi')?->getClientOriginalName(), $request->user()->id);
                }
                foreach ($documentationPaths as $index => $path) {
                    $file = $request->file('dokumentasi_servis')[$index] ?? null;
                    $this->addDocument($assetMaintenance, 'dokumentasi_servis', $path, $file?->getClientOriginalName(), $request->user()->id);
                }
                if ($paths['foto_setelah']) {
                    $this->addDocument($assetMaintenance, 'foto_setelah', $paths['foto_setelah'], $request->file('foto_setelah')?->getClientOriginalName(), $request->user()->id);
                }

                app(RiwayatPersetujuanService::class)->record(self::HISTORY_TYPE, $assetMaintenance, 'selesai', 'selesai', $request->input('catatan_hasil'), $request->user());
            });
        } catch (\Throwable $exception) {
            foreach (array_filter([$paths['foto_setelah'] ?? null, $paths['invoice'] ?? null, $paths['video_dokumentasi'] ?? null, ...($documentationPaths ?? [])]) as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return back()->with('success', 'Pemeliharaan selesai dan kondisi aset pada Master Data diperbarui.');
    }

    public function updateReportData(UpdateMaintenanceReportDataRequest $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        abort_unless($this->canView($request, $assetMaintenance), 403);
        if ($assetMaintenance->status !== AssetMaintenanceStatus::Completed) {
            throw ValidationException::withMessages(['status' => 'Data Berita Acara hanya dapat dilengkapi setelah pemeliharaan selesai.']);
        }

        $wasReportCreated = filled($assetMaintenance->berita_acara_dibuat_pada);

        $assetMaintenance->update([
            ...$request->validated(),
            'berita_acara_dibuat_pada' => $assetMaintenance->berita_acara_dibuat_pada ?: now(),
            'berita_acara_dibuat_oleh' => $assetMaintenance->berita_acara_dibuat_oleh ?: $request->user()->id,
        ]);

        if (! $wasReportCreated) {
            app(RiwayatPersetujuanService::class)->record(self::HISTORY_TYPE, $assetMaintenance, 'berita_acara', 'dibuat', 'Data Berita Acara Pemeliharaan dilengkapi.', $request->user());
        }

        return back()->with('success', 'Data Berita Acara Pemeliharaan berhasil disimpan.');
    }

    public function destroy(Request $request, AssetMaintenance $assetMaintenance): RedirectResponse
    {
        $this->authorizeAssetTeam($request);

        $assetMaintenance->load('documents');
        $paths = collect([
            $assetMaintenance->foto_sebelum,
            $assetMaintenance->foto_setelah,
        ])
            ->merge($assetMaintenance->documents->pluck('path_file'))
            ->filter()
            ->unique()
            ->values();

        DB::transaction(function () use ($assetMaintenance): void {
            $assetMaintenance->documents()->delete();
            $assetMaintenance->delete();
        });

        foreach ($paths as $path) {
            Storage::disk('public')->delete($path);
        }

        return back()->with('success', 'Data pemeliharaan berhasil dihapus.');
    }

    private function addDocument(AssetMaintenance $assetMaintenance, string $jenis, string $path, ?string $name, ?int $userId): AssetMaintenanceDocument
    {
        return AssetMaintenanceDocument::query()->create([
            'pemeliharaan_aset_id' => $assetMaintenance->id,
            'jenis_dokumen' => $jenis,
            'path_file' => $path,
            'nama_file' => $name,
            'uploaded_by' => $userId,
        ]);
    }

    public function downloadDocument(Request $request, AssetMaintenanceDocument $document)
    {
        abort_unless($this->canView($request, $document->maintenance), 403);
        abort_unless(Storage::disk('public')->exists($document->path_file), 404);

        return Storage::disk('public')->download($document->path_file, $document->nama_file ?: basename($document->path_file));
    }

    public function pdf(Request $request, AssetMaintenance $assetMaintenance): Response
    {
        abort_unless($this->canView($request, $assetMaintenance), 403);
        if ($assetMaintenance->status !== AssetMaintenanceStatus::Completed) {
            throw ValidationException::withMessages(['status' => 'Berita Acara hanya dapat dibuat setelah pemeliharaan selesai.']);
        }

        $assetMaintenance->load(['asset.category', 'asset.kodeBarang', 'applicant', 'verifier', 'completedBy', 'documents']);
        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('reports.asset-maintenance', ['maintenance' => $assetMaintenance])->render());
        $dompdf->setPaper('a4');
        $dompdf->render();

        return response($dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="berita-acara-pemeliharaan-'.$assetMaintenance->id.'.pdf"',
        ]);
    }

    public function word(Request $request, AssetMaintenance $assetMaintenance, MaintenanceReportWordService $reportService): BinaryFileResponse|RedirectResponse
    {
        abort_unless($this->canView($request, $assetMaintenance), 403);
        if ($assetMaintenance->status !== AssetMaintenanceStatus::Completed) {
            throw ValidationException::withMessages(['status' => 'Berita Acara hanya dapat dibuat setelah pemeliharaan selesai.']);
        }

        $assetMaintenance->load(['asset.category', 'asset.kodeBarang', 'applicant', 'verifier', 'completedBy']);
        if (! $assetMaintenance->hasCompleteReportData()) {
            return back()->withErrors(['berita_acara' => 'Data Berita Acara belum lengkap. Silakan lengkapi data Berita Acara terlebih dahulu.']);
        }

        $generated = $reportService->generate($assetMaintenance);

        return response()->download(
            $generated['path'],
            $generated['filename'],
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        )->deleteFileAfterSend(false);
    }

    private function maintenanceOptions()
    {
        return Asset::query()
            ->with(['category', 'kodeBarang', 'conditionStocks'])
            ->whereNull('tanggal_penghapusan')
            ->where('status_verifikasi', 'disetujui')
            ->orderBy('nama_aset')
            ->get()
            ->flatMap(function (Asset $asset) {
                $summary = $asset->condition_stock_summary;

                return collect([AssetCondition::HeavyDamage, AssetCondition::MediumDamage, AssetCondition::LightDamage])
                    ->flatMap(function (AssetCondition $condition) use ($asset, $summary) {
                        $total = (int) ($summary[$condition->value] ?? 0);
                        if ($total < 1) {
                            return [];
                        }

                        $active = $this->activeMaintenanceQuantity($asset->id, $condition->value);
                        $available = max(0, $total - $active);
                        if ($available < 1) {
                            return [];
                        }

                        return collect(range(1, $available))->map(function (int $index) use ($asset, $condition, $total, $active) {
                            $unitNumber = $active + $index;

                            return [
                                'value' => $asset->id.'|'.$condition->value.'|'.$unitNumber,
                                'label' => sprintf(
                                    '%s - %s / %s / %s / %s / Unit %d dari %d',
                                    $asset->display_code,
                                    $asset->nama_aset,
                                    $asset->merek_tipe ?: '-',
                                    $asset->lokasi_penyimpanan ?: '-',
                                    $condition->label(),
                                    $unitNumber,
                                    $total,
                                ),
                            ];
                        });
                    });
            })
            ->values();
    }

    private function parseMaintenanceUnit(string $value): array
    {
        $parts = explode('|', $value);
        $assetId = (int) ($parts[0] ?? 0);
        $condition = $parts[1] ?? '';

        if ($assetId < 1 || ! in_array($condition, [
            AssetCondition::LightDamage->value,
            AssetCondition::MediumDamage->value,
            AssetCondition::HeavyDamage->value,
        ], true)) {
            throw ValidationException::withMessages(['maintenance_unit' => 'Pilihan unit pemeliharaan tidak valid.']);
        }

        return [$assetId, $condition];
    }

    private function availableMaintenanceStock(Asset $asset, string $condition): int
    {
        $asset->loadMissing('conditionStocks');
        $summary = $asset->condition_stock_summary;
        $total = (int) ($summary[$condition] ?? 0);

        return max(0, $total - $this->activeMaintenanceQuantity($asset->id, $condition));
    }

    private function activeMaintenanceQuantity(int $assetId, string $condition): int
    {
        return (int) AssetMaintenance::query()
            ->where('asset_id', $assetId)
            ->where('kondisi_sebelum', $condition)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->sum(DB::raw('COALESCE(jumlah_dipelihara, 1)'));
    }

    private function authorizeAssetTeam(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['tim_aset', 'super_admin'], true), 403);
    }

    private function canView(Request $request, AssetMaintenance $maintenance): bool
    {
        $role = $request->user()?->role;

        return in_array($role, ['admin', 'tim_aset', 'kasubbag_umum_perencanaan', 'super_admin'], true)
            || ($role === 'admin_bidang' && $maintenance->pemohon_id === $request->user()?->id);
    }
}
