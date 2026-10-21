<?php

namespace App\Services;

use App\Enums\AssetCondition;
use App\Enums\AssetDisposalStatus;
use App\Enums\LoanStatus;
use App\Enums\ProcurementProposalStatus;
use App\Enums\UserRole;
use App\Enums\WorkUnit;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDisposalRequest;
use App\Models\AssetMaintenance;
use App\Models\DetailKir;
use App\Models\Kir;
use App\Models\KodeBarang;
use App\Models\KodeInternalPrefix;
use App\Models\KodeLokasi;
use App\Models\KodePersediaan;
use App\Models\LoanRequest;
use App\Models\PermintaanPersediaan;
use App\Models\Procurement;
use App\Models\ProcurementProposal;
use App\Models\Role;
use App\Models\Ruangan;
use App\Models\StockOpnameCategory;
use App\Models\StockOpnameItem;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

class DataExportService
{
    public function download(Request $request, string $dataset, string $format): Response
    {
        $export = $this->dataset($request, $dataset);
        abort_unless($export, 404);
        abort_unless($this->canAccess($request, $dataset), 403);

        $headers = $export['headers'];
        $rows = $export['rows'];
        $title = $export['title'];
        $filename = str($dataset)->slug().'-'.now()->format('Y-m-d').'.'.$this->extension($format);
        $printedAt = now()->translatedFormat('d F Y H:i');
        $filterInfo = $this->filterInfo($request);

        return match ($format) {
            'word' => $this->word($title, $headers, $rows, $printedAt, $filterInfo, $filename),
            'pdf' => $this->pdf($title, $headers, $rows, $printedAt, $filterInfo, $filename),
            'excel' => $this->excel($title, $headers, $rows, $printedAt, $filterInfo, $filename),
            default => abort(404),
        };
    }

    private function dataset(Request $request, string $dataset): ?array
    {
        return match ($dataset) {
            'aset' => $this->aset($request),
            'stock-opname' => $this->stockOpname($request),
            'permintaan-persediaan' => $this->permintaanPersediaan($request),
            'peminjaman' => $this->peminjaman($request),
            'pemeliharaan-aset' => $this->pemeliharaan($request),
            'usulan-pengadaan' => $this->usulanPengadaan($request),
            'pengadaan' => $this->pengadaan($request),
            'penghapusan-aset' => $this->penghapusan($request),
            'persetujuan' => $this->persetujuan($request),
            'kategori-aset' => $this->kategoriAset($request),
            'kategori-stock-opname' => $this->kategoriStockOpname($request),
            'ruangan' => $this->ruangan($request),
            'kir' => $this->kirIndex($request),
            'kir-detail' => $this->kirDetail($request),
            'users' => $this->users($request),
            'roles' => $this->roles(),
            'kodefikasi-barang' => $this->kodefikasi($request, 'barang'),
            'kodefikasi-persediaan' => $this->kodefikasi($request, 'persediaan'),
            'kodefikasi-lokasi' => $this->kodefikasi($request, 'lokasi'),
            'kodefikasi-internal' => $this->kodefikasiInternal($request),
            'laporan-aset' => $this->aset($request, 'Laporan Data Aset'),
            default => null,
        };
    }

    private function aset(Request $request, string $title = 'Data Aset'): array
    {
        $query = Asset::query()
            ->with(['category', 'kodeBarang', 'conditionStocks'])
            ->whereNull('tanggal_penghapusan');

        $this->assetFilters($query, $request);

        $rows = $query->latest()->get()->map(fn (Asset $asset, int $i) => [
            $i + 1,
            $asset->display_code,
            $asset->nama_aset,
            $asset->kodeBarang?->kode_barang ?: '-',
            $this->conditionSummary($asset),
            $this->label($asset->status_verifikasi),
            $asset->lokasi_penyimpanan ?: '-',
            (int) $asset->jumlah,
            $asset->category?->nama_kategori ?: '-',
            $asset->merek_tipe ?: '-',
        ]);

        return ['title' => $title, 'headers' => ['No', 'Kode Internal', 'Nama Aset', 'Kode Barang', 'Kondisi', 'Verifikasi', 'Lokasi', 'Stok', 'Kategori', 'Merek/Tipe'], 'rows' => $rows];
    }

    private function stockOpname(Request $request): array
    {
        $query = StockOpnameItem::query()->with(['category', 'kodePersediaan', 'kodeBarang', 'requester']);
        $this->stockFilters($query, $request);

        $rows = $query->latest()->get()->map(fn (StockOpnameItem $item, int $i) => [
            $i + 1,
            $item->kode_persediaan ?: $item->kodePersediaan?->kode_persediaan ?: '-',
            $item->kode_internal ?: '-',
            $item->nama_barang,
            $item->category?->nama_kategori ?: '-',
            $item->satuan ?: '-',
            (int) $item->stok,
            $this->stockStatusLabel((int) $item->stok),
            $item->status_verifikasi?->label() ?: $this->label($item->status_verifikasi),
            $item->lokasi_penyimpanan ?: '-',
        ]);

        return ['title' => 'Stock Opname / Persediaan', 'headers' => ['No', 'Kode Persediaan', 'Kode Internal', 'Nama Barang', 'Kategori', 'Satuan', 'Stok', 'Status Stok', 'Status Verifikasi', 'Lokasi'], 'rows' => $rows];
    }

    private function permintaanPersediaan(Request $request): array
    {
        $query = PermintaanPersediaan::query()->with(['pemohon', 'details']);
        if ($this->role($request) === UserRole::AdminBidang->value) {
            $query->where('pemohon_id', $request->user()->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('nomor_permintaan', 'like', "%{$search}%")
                ->orWhere('keperluan', 'like', "%{$search}%")
                ->orWhereHas('pemohon', fn (Builder $u) => $u->where('name', 'like', "%{$search}%")->orWhere('nip', 'like', "%{$search}%"))
                ->orWhereHas('details', fn (Builder $d) => $d
                    ->where('nama_barang_snapshot', 'like', "%{$search}%")
                    ->orWhere('justifikasi_kebutuhan', 'like', "%{$search}%")));
        }

        $number = 0;
        $rows = $query->latest()->get()->flatMap(function (PermintaanPersediaan $item) use (&$number) {
            return $item->details->map(fn ($detail) => [
                ++$number,
                $item->nomor_permintaan,
                $item->pemohon?->name ?: '-',
                WorkUnit::labelFor($item->pemohon?->work_unit),
                $item->pemohon?->nip ?: '-',
                $this->date($item->tanggal_permintaan),
                $detail->nama_barang_snapshot,
                $detail->jumlah_diminta,
                $detail->satuan_snapshot,
                $detail->justifikasi_kebutuhan ?: ($item->keperluan ?: '-'),
                $item->status?->label() ?: $this->label($item->status),
            ]);
        });

        return ['title' => 'Permintaan Barang Persediaan', 'headers' => ['No', 'Nomor Permintaan', 'Pemohon', 'Unit Kerja', 'NIP', 'Tanggal', 'Nama Barang', 'Jumlah', 'Satuan', 'Justifikasi Kebutuhan', 'Status'], 'rows' => $rows];
    }

    private function peminjaman(Request $request): array
    {
        $query = LoanRequest::query()->with(['requester', 'asset', 'details.asset']);
        if ($this->role($request) === UserRole::AdminBidang->value) {
            $query->where('pemohon_id', $request->user()->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $rows = $query->latest()->get()->map(fn (LoanRequest $loan, int $i) => [
            $i + 1,
            '#'.$loan->id,
            $loan->requester?->name ?: '-',
            WorkUnit::labelFor($loan->requester?->work_unit),
            $loan->requester?->nip ?: '-',
            $loan->details->isNotEmpty()
                ? $loan->details->map(fn ($detail) => ($detail->asset?->display_code ?: '-').' - '.($detail->asset?->nama_aset ?: '-'))->implode('; ')
                : ($loan->asset?->nama_aset ?: '-'),
            $loan->itemCount(),
            $this->date($loan->tanggal_pinjam),
            $this->date($loan->tanggal_kembali),
            $loan->status?->label() ?: $this->label($loan->status),
        ]);

        return ['title' => 'Peminjaman Barang', 'headers' => ['No', 'Nomor/ID', 'Peminjam', 'Unit Kerja', 'NIP', 'Aset', 'Jumlah', 'Tanggal Pinjam', 'Tanggal Kembali', 'Status'], 'rows' => $rows];
    }

    private function pemeliharaan(Request $request): array
    {
        $query = AssetMaintenance::query()->with(['asset', 'applicant']);
        if ($this->role($request) === UserRole::AdminBidang->value) {
            $query->where('pemohon_id', $request->user()->id);
        }
        foreach (['kondisi' => 'kondisi_sebelum', 'prioritas' => 'prioritas', 'status' => 'status'] as $param => $column) {
            if ($request->filled($param)) {
                $query->where($column, $request->string($param));
            }
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->whereHas('asset', fn (Builder $a) => $a->where('kode_internal', 'like', "%{$search}%")->orWhere('nama_aset', 'like', "%{$search}%"))
                ->orWhereHas('applicant', fn (Builder $u) => $u->where('name', 'like', "%{$search}%")));
        }

        $rows = $query->latest()->get()->map(fn (AssetMaintenance $item, int $i) => [
            $i + 1,
            $item->asset?->display_code ?: '-',
            $item->asset?->nama_aset ?: '-',
            $item->kondisi_sebelum?->label() ?: '-',
            $item->prioritas?->label() ?: '-',
            $item->status?->label() ?: '-',
            $this->date($item->tanggal_pengajuan),
            $this->date($item->tanggal_mulai),
            $this->date($item->tanggal_selesai),
            $item->penyedia_servis ?: '-',
            $item->biaya_servis ?: '-',
        ]);

        return ['title' => 'Pemeliharaan Aset', 'headers' => ['No', 'Kode Internal', 'Nama Aset', 'Kondisi Sebelum', 'Prioritas', 'Status', 'Tanggal Pengajuan', 'Tanggal Mulai', 'Tanggal Selesai', 'Penyedia Servis', 'Biaya Servis'], 'rows' => $rows];
    }

    private function usulanPengadaan(Request $request): array
    {
        $query = ProcurementProposal::query()->with(['requester']);
        if ($this->role($request) === UserRole::AdminBidang->value) {
            $query->where('pengusul_id', $request->user()->id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $query->where('nama_barang', 'like', '%'.$request->string('search')->toString().'%');
        }

        $rows = $query->latest()->get()->map(fn (ProcurementProposal $item, int $i) => [
            $i + 1,
            '#'.$item->id,
            $item->requester?->name ?: '-',
            WorkUnit::labelFor($item->requester?->work_unit),
            $item->nama_barang,
            (int) $item->jumlah,
            $item->spesifikasi ?: '-',
            $item->alasan_kebutuhan ?: '-',
            $item->status?->label() ?: '-',
        ]);

        return ['title' => 'Usulan Pengadaan', 'headers' => ['No', 'Nomor', 'Pengaju', 'Unit Kerja', 'Barang', 'Jumlah', 'Spesifikasi', 'Alasan', 'Status'], 'rows' => $rows];
    }

    private function pengadaan(Request $request): array
    {
        $query = Procurement::query()->with(['creator']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('nama_barang', 'like', "%{$search}%")->orWhere('pemasok', 'like', "%{$search}%"));
        }

        $rows = $query->latest()->get()->map(fn (Procurement $item, int $i) => [
            $i + 1, '#'.$item->id, $item->nama_barang, (int) $item->jumlah_dipesan, $item->creator?->name ?: '-', $this->date($item->tanggal_pesan), $item->status?->label() ?: '-',
        ]);

        return ['title' => 'Pengadaan Barang', 'headers' => ['No', 'Nomor Pengadaan', 'Barang', 'Jumlah', 'Pengaju', 'Tanggal', 'Status'], 'rows' => $rows];
    }

    private function penghapusan(Request $request): array
    {
        $query = AssetDisposalRequest::query()->with(['requester', 'asset']);
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $rows = $query->latest()->get()->map(fn (AssetDisposalRequest $item, int $i) => [
            $i + 1, $item->asset?->display_code ?: '-', $item->asset?->nama_aset ?: '-', $item->asset?->kondisi?->label() ?: '-', $item->alasan?->label() ?: '-', $item->requester?->name ?: '-', $this->date($item->created_at), $item->status?->label() ?: '-',
        ]);

        return ['title' => 'Penghapusan Aset', 'headers' => ['No', 'Kode Internal', 'Nama Aset', 'Kondisi', 'Alasan', 'Pengaju', 'Tanggal', 'Status'], 'rows' => $rows];
    }

    private function persetujuan(Request $request): array
    {
        $jenis = $request->string('jenis', 'all')->toString();
        $rows = collect()
            ->concat($this->approvalLoans())
            ->concat($this->approvalProposals())
            ->concat($this->approvalDisposals())
            ->when($jenis !== 'all', fn (Collection $items) => $items->where('jenis_key', $jenis))
            ->sortByDesc('tanggal_sort')
            ->values()
            ->map(fn (array $item, int $i) => [$i + 1, $item['jenis'], $item['pengaju'], $item['unit_kerja'] ?? '-', $item['barang'], $item['jumlah'], $item['status'], $item['tanggal'], $item['diproses_oleh'], $item['tanggal_proses']]);

        return ['title' => 'Persetujuan', 'headers' => ['No', 'Jenis Pengajuan', 'Pengaju', 'Unit Kerja', 'Barang/Aset', 'Jumlah', 'Status', 'Tanggal Pengajuan', 'Terakhir Diproses Oleh', 'Tanggal Terakhir Proses'], 'rows' => $rows];
    }

    private function kategoriAset(Request $request): array
    {
        $query = AssetCategory::query()->withCount('assets');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('kode_kategori', 'like', "%{$search}%")->orWhere('nama_kategori', 'like', "%{$search}%")->orWhere('deskripsi', 'like', "%{$search}%"));
        }

        return ['title' => 'Kategori Aset', 'headers' => ['No', 'Kode Kategori', 'Nama Kategori', 'Jumlah Aset', 'Keterangan'], 'rows' => $query->latest()->get()->map(fn ($item, $i) => [$i + 1, $item->kode_kategori, $item->nama_kategori, $item->assets_count, $item->deskripsi ?: '-'])];
    }

    private function kategoriStockOpname(Request $request): array
    {
        $query = StockOpnameCategory::query()->withCount('items');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('nama_kategori', 'like', "%{$search}%")->orWhere('keterangan', 'like', "%{$search}%"));
        }

        return ['title' => 'Kategori Stock Opname', 'headers' => ['No', 'Nama Kategori', 'Jumlah Barang', 'Keterangan'], 'rows' => $query->latest()->get()->map(fn ($item, $i) => [$i + 1, $item->nama_kategori, $item->items_count, $item->keterangan ?: '-'])];
    }

    private function ruangan(Request $request): array
    {
        $query = Ruangan::query()->with(['penanggungJawab', 'kodeLokasi'])->withCount('kirDetails');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('kode_ruangan', 'like', "%{$search}%")->orWhere('nama_ruangan', 'like', "%{$search}%")->orWhereHas('penanggungJawab', fn (Builder $u) => $u->where('name', 'like', "%{$search}%")));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        return ['title' => 'Manajemen Ruangan', 'headers' => ['No', 'Kode Ruangan', 'Nama Ruangan', 'Kode Lokasi', 'Penanggung Jawab', 'Jumlah Aset', 'Status', 'Keterangan'], 'rows' => $query->latest()->get()->map(fn (Ruangan $item, $i) => [$i + 1, $item->kode_ruangan, $item->nama_ruangan, $item->kodeLokasi?->kode_lokasi ?: '-', $item->penanggungJawab?->name ?: '-', $item->kir_details_count, $item->status?->label() ?: '-', $item->keterangan ?: '-'])];
    }

    private function kirIndex(Request $request): array
    {
        $query = Ruangan::query()->with(['penanggungJawab', 'kodeLokasi'])->withCount('kirDetails');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('kode_ruangan', 'like', "%{$search}%")->orWhere('nama_ruangan', 'like', "%{$search}%"));
        }

        return ['title' => 'Kartu Inventaris Ruangan', 'headers' => ['No', 'Kode Ruangan', 'Nama Ruangan', 'Kode Lokasi', 'Penanggung Jawab', 'Jumlah Aset'], 'rows' => $query->latest()->get()->map(fn (Ruangan $item, $i) => [$i + 1, $item->kode_ruangan, $item->nama_ruangan, $item->kodeLokasi?->kode_lokasi ?: '-', $item->penanggungJawab?->name ?: '-', $item->kir_details_count])];
    }

    private function kirDetail(Request $request): array
    {
        $kir = Kir::query()->where('ruangan_id', $request->integer('ruangan_id'))->firstOrFail();
        $query = DetailKir::query()->where('kir_id', $kir->id);
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('nibar', 'like', "%{$search}%")->orWhere('nama_barang', 'like', "%{$search}%")->orWhere('kode_barang', 'like', "%{$search}%"));
        }

        return ['title' => 'Detail Kartu Inventaris Ruangan', 'headers' => ['No', 'NIBAR', 'Kode Barang', 'Nama Barang', 'Merek/Tipe', 'Tahun', 'Jumlah', 'Satuan', 'Keterangan'], 'rows' => $query->latest()->get()->map(fn (DetailKir $item, $i) => [$i + 1, $item->nibar, $item->kode_barang, $item->nama_barang, $item->merek_tipe ?: '-', $item->tahun_perolehan ?: '-', $item->jumlah, $item->satuan ?: '-', $item->keterangan ?: '-'])];
    }

    private function users(Request $request): array
    {
        $query = User::query()->with('roleData');
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $matchingWorkUnits = collect(WorkUnit::options())
                ->filter(fn (string $label, string $value): bool => str_contains(strtolower($label), strtolower($search)) || str_contains(strtolower($value), strtolower($search)))
                ->keys()
                ->all();

            $query->where(fn (Builder $q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('nip', 'like', "%{$search}%")
                ->orWhere('work_unit', 'like', "%{$search}%")
                ->when($matchingWorkUnits !== [], fn (Builder $unitQuery) => $unitQuery->orWhereIn('work_unit', $matchingWorkUnits)));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->string('role'));
        }
        if ($request->filled('work_unit')) {
            $query->where('work_unit', $request->string('work_unit'));
        }
        if ($request->has('is_active') && $request->string('is_active')->toString() !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return ['title' => 'Users', 'headers' => ['No', 'Nama', 'NIP/NIK', 'Email', 'Role', 'Unit Kerja', 'Status'], 'rows' => $query->latest()->get()->map(fn (User $user, $i) => [$i + 1, $user->name, $user->nip ?: '-', $user->email, $user->roleData?->nama ?: UserRole::tryFrom($user->role)?->label() ?: $this->label($user->role), WorkUnit::labelFor($user->work_unit), $user->is_active ? 'Aktif' : 'Tidak Aktif'])];
    }

    private function roles(): array
    {
        return ['title' => 'Roles', 'headers' => ['No', 'Kode', 'Nama Role', 'Jumlah User', 'Sistem'], 'rows' => Role::query()->withCount('users')->latest()->get()->map(fn (Role $role, $i) => [$i + 1, $role->kode, $role->nama, $role->users_count, $role->is_system ? 'Ya' : 'Tidak'])];
    }

    private function kodefikasi(Request $request, string $type): array
    {
        $model = match ($type) {
            'persediaan' => KodePersediaan::class,
            'lokasi' => KodeLokasi::class,
            default => KodeBarang::class,
        };
        $query = $model::query();
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $type === 'lokasi'
                ? $q->where('kode_lokasi', 'like', "%{$search}%")->orWhere('nama_lokasi', 'like', "%{$search}%")
                : $q->where($type === 'persediaan' ? 'kode_persediaan' : 'kode_barang', 'like', "%{$search}%")->orWhere('nama_barang', 'like', "%{$search}%"));
        }

        if ($type === 'lokasi') {
            return ['title' => 'Kodefikasi Lokasi', 'headers' => ['No', 'Kode Lokasi', 'Nama Lokasi', 'Keterangan'], 'rows' => $query->orderBy('kode_lokasi')->get()->map(fn ($item, $i) => [$i + 1, $item->kode_lokasi, $item->nama_lokasi, $item->keterangan ?: '-'])];
        }

        $codeColumn = $type === 'persediaan' ? 'kode_persediaan' : 'kode_barang';
        return ['title' => $type === 'persediaan' ? 'Kodefikasi Persediaan' : 'Kodefikasi Barang', 'headers' => ['No', 'Kode', 'Nama'], 'rows' => $query->orderBy($codeColumn)->get()->map(fn ($item, $i) => [$i + 1, $item->{$codeColumn}, $item->nama_barang])];
    }

    private function kodefikasiInternal(Request $request): array
    {
        $query = Asset::query()->with(['category', 'kodeBarang'])->whereNull('tanggal_penghapusan');
        $this->assetFilters($query, $request);
        if ($request->input('status_kode') === 'sudah') {
            $query->whereNotNull('kode_internal');
        }
        if ($request->input('status_kode') === 'belum') {
            $query->whereNull('kode_internal');
        }

        return ['title' => 'Kodefikasi Internal', 'headers' => ['No', 'Kode Internal', 'Kode Barang', 'Nama Aset', 'Kategori', 'Lokasi'], 'rows' => $query->latest()->get()->map(fn (Asset $asset, $i) => [$i + 1, $asset->kode_internal ?: '-', $asset->kodeBarang?->kode_barang ?: '-', $asset->nama_aset, $asset->category?->nama_kategori ?: '-', $asset->lokasi_penyimpanan ?: '-'])];
    }

    private function assetFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('kode_internal', 'like', "%{$search}%")->orWhere('nama_aset', 'like', "%{$search}%")->orWhere('merek_tipe', 'like', "%{$search}%")->orWhereHas('kodeBarang', fn (Builder $k) => $k->where('kode_barang', 'like', "%{$search}%")));
        }
        if ($request->integer('kategori_id')) {
            $query->where('kategori_aset_id', $request->integer('kategori_id'));
        }
        if ($request->filled('kondisi')) {
            $query->whereHas('conditionStocks', fn (Builder $q) => $q->where('kondisi', $request->string('kondisi'))->where('jumlah', '>', 0));
        }
        if ($request->filled('status_verifikasi')) {
            $query->where('status_verifikasi', $request->string('status_verifikasi'));
        }
        if ($request->filled('lokasi')) {
            $query->where('lokasi_penyimpanan', $request->string('lokasi'));
        }
    }

    private function stockFilters(Builder $query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(fn (Builder $q) => $q->where('kode_internal', 'like', "%{$search}%")->orWhere('kode_persediaan', 'like', "%{$search}%")->orWhere('nama_barang', 'like', "%{$search}%")->orWhere('merek_tipe', 'like', "%{$search}%")->orWhere('lokasi_penyimpanan', 'like', "%{$search}%"));
        }
        if ($request->filled('category')) {
            $query->where('stock_opname_category_id', $request->integer('category'));
        }
        if ($request->filled('verification')) {
            $query->where('status_verifikasi', $request->string('verification'));
        }
        match ($request->string('stock_status')->toString()) {
            'rendah', 'menipis' => $query->where('stok', '>', 0)->where('stok', '<', 5),
            'tersedia' => $query->where('stok', '>=', 5),
            'habis' => $query->where('stok', 0),
            default => null,
        };
    }

    private function approvalLoans(): Collection
    {
        return LoanRequest::query()->with(['requester', 'asset', 'details.asset', 'reviewer', 'handedOverBy', 'returnedBy'])->latest()->get()->map(fn (LoanRequest $loan) => [
            'jenis_key' => 'loan', 'jenis' => 'Peminjaman', 'pengaju' => $loan->requester?->name ?: '-', 'unit_kerja' => WorkUnit::labelFor($loan->requester?->work_unit), 'barang' => $loan->itemSummary(), 'jumlah' => $loan->itemCount(), 'status' => $loan->status?->label() ?: '-', 'tanggal' => $this->date($loan->created_at), 'tanggal_sort' => $loan->created_at, 'diproses_oleh' => $loan->reviewer?->name ?: $loan->handedOverBy?->name ?: $loan->returnedBy?->name ?: '-', 'tanggal_proses' => $this->date($loan->tanggal_pemeriksaan ?: $loan->tanggal_penyerahan ?: $loan->tanggal_pengembalian),
        ]);
    }

    private function approvalProposals(): Collection
    {
        return ProcurementProposal::query()->with(['requester', 'assetTeamReviewer', 'supervisorReviewer'])->latest()->get()->map(fn (ProcurementProposal $item) => [
            'jenis_key' => 'procurement', 'jenis' => 'Pengadaan', 'pengaju' => $item->requester?->name ?: '-', 'unit_kerja' => WorkUnit::labelFor($item->requester?->work_unit), 'barang' => $item->nama_barang, 'jumlah' => $item->jumlah, 'status' => $item->status?->label() ?: '-', 'tanggal' => $this->date($item->created_at), 'tanggal_sort' => $item->created_at, 'diproses_oleh' => $item->supervisorReviewer?->name ?: $item->assetTeamReviewer?->name ?: '-', 'tanggal_proses' => $this->date($item->tanggal_pemeriksaan_kasubbag ?: $item->tanggal_pemeriksaan_tim_aset),
        ]);
    }

    private function approvalDisposals(): Collection
    {
        return AssetDisposalRequest::query()->with(['requester', 'asset', 'reviewer'])->latest()->get()->map(fn (AssetDisposalRequest $item) => [
            'jenis_key' => 'disposal', 'jenis' => 'Penghapusan', 'pengaju' => $item->requester?->name ?: '-', 'unit_kerja' => '-', 'barang' => $item->asset?->nama_aset ?: '-', 'jumlah' => '-', 'status' => $item->status?->label() ?: '-', 'tanggal' => $this->date($item->created_at), 'tanggal_sort' => $item->created_at, 'diproses_oleh' => $item->reviewer?->name ?: '-', 'tanggal_proses' => $this->date($item->tanggal_pemeriksaan),
        ]);
    }

    private function canAccess(Request $request, string $dataset): bool
    {
        return $request->user() !== null && in_array($dataset, [
            'aset',
            'laporan-aset',
            'stock-opname',
            'permintaan-persediaan',
            'peminjaman',
            'pemeliharaan-aset',
            'usulan-pengadaan',
            'pengadaan',
            'penghapusan-aset',
            'persetujuan',
            'kategori-aset',
            'kategori-stock-opname',
            'ruangan',
            'kir',
            'kir-detail',
            'users',
            'roles',
            'kodefikasi-barang',
            'kodefikasi-persediaan',
            'kodefikasi-lokasi',
            'kodefikasi-internal',
        ], true);
    }

    private function word(string $title, array $headers, Collection $rows, string $printedAt, string $filterInfo, string $filename): BinaryFileResponse
    {
        $word = new PhpWord();
        $section = $word->addSection(['orientation' => count($headers) > 7 ? 'landscape' : 'portrait']);
        $section->addText(strtoupper($title), ['bold' => true, 'size' => 14]);
        $section->addText('SIMIVA - Dinas Komunikasi dan Informatika Kabupaten Siak', ['size' => 9]);
        $section->addText("Tanggal Cetak: {$printedAt}", ['size' => 8]);
        $section->addText("Filter: {$filterInfo}", ['size' => 8]);
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'D8E2EE', 'cellMargin' => 80]);
        $table->addRow();
        foreach ($headers as $header) {
            $table->addCell(1800)->addText($header, ['bold' => true, 'size' => 8]);
        }
        foreach ($rows as $row) {
            $table->addRow();
            foreach ($row as $value) {
                $table->addCell(1800)->addText((string) $value, ['size' => 8]);
            }
        }
        if ($rows->isEmpty()) {
            $table->addRow();
            $table->addCell(1800 * count($headers), ['gridSpan' => count($headers)])->addText('Tidak ada data');
        }

        $basePath = tempnam(sys_get_temp_dir(), 'simiva-word-');
        @unlink($basePath);
        $path = $basePath.'.docx';
        (new Word2007($word))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function pdf(string $title, array $headers, Collection $rows, string $printedAt, string $filterInfo, string $filename): Response
    {
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('exports.table', compact('title', 'headers', 'rows', 'printedAt', 'filterInfo'))->render());
        $dompdf->setPaper('A4', count($headers) > 7 ? 'landscape' : 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function excel(string $title, array $headers, Collection $rows, string $printedAt, string $filterInfo, string $filename): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $sheet->fromArray([[$title], ['Tanggal Cetak', $printedAt], ['Filter', $filterInfo], [], $headers], null, 'A1');
        $sheet->fromArray($rows->values()->all(), null, 'A6');
        foreach (range(1, count($headers)) as $index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        $basePath = tempnam(sys_get_temp_dir(), 'simiva-excel-');
        @unlink($basePath);
        $path = $basePath.'.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    private function extension(string $format): string
    {
        return ['word' => 'docx', 'pdf' => 'pdf', 'excel' => 'xlsx'][$format];
    }

    private function filterInfo(Request $request): string
    {
        $filters = collect($request->except(['page', 'per_page']))
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $key) => str($key)->headline().': '.$value)
            ->implode('; ');

        return $filters !== '' ? $filters : 'Semua Data';
    }

    private function role(Request $request): ?string
    {
        $role = $request->user()?->role;

        return $role instanceof UserRole ? $role->value : $role;
    }

    private function date(mixed $value): string
    {
        return $value ? $value->translatedFormat('d M Y H:i') : '-';
    }

    private function label(mixed $value): string
    {
        if ($value instanceof \BackedEnum && method_exists($value, 'label')) {
            return $value->label();
        }

        return str((string) $value)->replace('_', ' ')->headline()->toString();
    }

    private function stockStatusLabel(int $stock): string
    {
        return match (true) {
            $stock === 0 => 'Stok Habis',
            $stock < 5 => 'Stok Menipis',
            default => 'Tersedia',
        };
    }

    private function conditionSummary(Asset $asset): string
    {
        $summary = $asset->condition_stock_summary;
        $parts = [];
        foreach (AssetCondition::cases() as $condition) {
            $amount = (int) ($summary[$condition->value] ?? 0);
            if ($amount > 0) {
                $parts[] = $condition->label().': '.$amount;
            }
        }

        return $parts ? implode(', ', $parts) : ($asset->kondisi?->label() ?: '-');
    }
}
