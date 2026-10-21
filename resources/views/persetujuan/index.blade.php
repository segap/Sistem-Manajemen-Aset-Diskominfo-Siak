@extends('layouts.app')

@section('title', 'Persetujuan - SIMIVA')

@php
    use App\Enums\AssetDisposalStatus;
    use App\Enums\LoanStatus;
    use App\Enums\ProcurementProposalStatus;
    use App\Enums\UserRole;
    use Illuminate\Support\Facades\Storage;

    $filters = [
        'all' => 'Semua',
        'loan' => 'Peminjaman',
        'procurement' => 'Pengadaan',
        'disposal' => 'Penghapusan',
    ];
    $typeLabels = [
        'loan' => 'Peminjaman',
        'procurement' => 'Usulan Pengadaan',
        'disposal' => 'Penghapusan Aset',
    ];
    $typeClass = fn ($type) => match ($type) {
        'loan' => 'bg-[#dbeafe] text-[#075985]',
        'procurement' => 'bg-[#dcfce7] text-[#166534]',
        'disposal' => 'bg-[#fee2e2] text-[#991b1b]',
        default => 'bg-[#eef2f7] text-[#475569]',
    };
    $statusClass = fn ($item) => match (true) {
        str_contains(strtolower($item['status_label']), 'dipinjam') => 'status-dipinjam',
        str_contains(strtolower($item['status_label']), 'dikembalikan') => 'status-dikembalikan',
        str_contains(strtolower($item['status_label']), 'ditolak') => 'status-ditolak',
        str_contains(strtolower($item['status_label']), 'disetujui') || str_contains(strtolower($item['status_label']), 'terverifikasi') => 'status-terverifikasi',
        default => 'status-diajukan',
    };
    $rupiah = fn ($value) => 'Rp '.number_format((float) $value, 0, ',', '.');
    $formatDate = fn ($date) => $date ? $date->translatedFormat('d M Y') : '-';
    $formatDateTime = fn ($date) => $date ? $date->translatedFormat('d M Y • H:i') : '-';
    $currentRole = auth()->user()?->role instanceof UserRole ? auth()->user()->role->value : auth()->user()?->role;
    $canDeleteApprovals = $currentRole === UserRole::SuperAdmin->value;
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Persetujuan</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Tinjau seluruh pengajuan yang membutuhkan keputusan Kasubbag.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>
            <span class="grid h-[19px] w-[19px] place-items-center rounded-full bg-[#24a576] text-white">
                <svg class="h-3 w-3 fill-none stroke-current stroke-[3] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M5 13l4 4L19 7" /></svg>
            </span>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="mb-5 grid grid-cols-4 gap-3.5 max-[900px]:grid-cols-2 max-[580px]:grid-cols-1">
        <article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]"><span class="block text-[9px] uppercase text-[#8190a2]">Total Menunggu</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $waitingCount }}</strong></article>
        <article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]"><span class="block text-[9px] uppercase text-[#8190a2]">Peminjaman</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $counts['loan'] }}</strong></article>
        <article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]"><span class="block text-[9px] uppercase text-[#8190a2]">Usulan Pengadaan</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $counts['procurement'] }}</strong></article>
        <article class="rounded-[14px] border border-[#e3e9f0] bg-white p-[18px] shadow-[0_8px_22px_rgb(35_63_92_/_4%)]"><span class="block text-[9px] uppercase text-[#8190a2]">Penghapusan</span><strong class="mt-2 block text-2xl font-extrabold text-[#0b2b50]">{{ $counts['disposal'] }}</strong></article>
    </div>

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <div class="flex min-h-[72px] items-center justify-between gap-5 border-b border-[#edf1f5] px-[18px] py-3.5 max-[780px]:flex-col max-[780px]:items-stretch">
            <div class="flex flex-wrap gap-2">
                @foreach ($filters as $value => $label)
                    <a class="page-action-button rounded-[9px] px-4 py-2 text-[10px] font-bold no-underline transition {{ $selectedType === $value ? 'bg-[#146bb5] text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)]' : 'bg-[#f1f5f9] text-[#66758a] hover:bg-[#e7eef6]' }}" href="{{ route('persetujuan.index', ['jenis' => $value]) }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-export-dropdown dataset="persetujuan" />
            </div>
        </div>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Jenis</th>
                        <th>Pengaju</th>
                        <th>Barang</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Tanggal Pengajuan</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visibleItems as $index => $item)
                        <tr>
                            <td class="number-column">{{ $index + 1 }}</td>
                            <td><span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-extrabold {{ $typeClass($item['type']) }}">{{ $typeLabels[$item['type']] }}</span></td>
                            <td>
                                <strong>{{ $item['requester'] }}</strong>
                                @if (in_array($item['type'] ?? null, ['loan', 'procurement'], true))
                                    <small class="cell-subtitle">{{ $item['requester_work_unit'] ?? '-' }}</small>
                                @endif
                            </td>
                            <td><strong>{{ $item['title'] }}</strong><small class="cell-subtitle">{{ $item['code'] }}</small></td>
                            <td>{{ $item['jumlah'] ?? '-' }}</td>
                            <td>
                                <div class="grid gap-1.5">
                                    <span class="loan-status approval-status-badge {{ $statusClass($item) }}">{{ $item['status_label'] }}</span>
                                    <small class="block text-[9px] font-semibold leading-relaxed text-[#66758a]">{{ $item['approval']['actor_label'] }}</small>
                                    <small class="block text-[9px] text-[#8a98aa]">{{ $formatDateTime($item['approval']['processed_at']) }}</small>
                                </div>
                            </td>
                            <td>{{ $formatDateTime($item['created_at']) }}</td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button type="button" data-modal-open="approval-{{ $item['type'] }}-{{ $item['id'] }}" title="Tinjau" aria-label="Tinjau {{ $item['title'] }}">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg>
                                    </button>
                                    @if ($canDeleteApprovals)
                                        <button class="text-[#c94c45]" type="button" data-modal-open="delete-approval-{{ $item['type'] }}-{{ $item['id'] }}" title="Hapus" aria-label="Hapus {{ $item['title'] }}">
                                            <svg class="stroke-[#c94c45]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="table-empty">
                                    <span><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11l2 2 4-5" /><path d="M5 4h14v16H5z" /></svg></span>
                                    <strong>Data belum tersedia</strong>
                                    <p>Belum ada data pengajuan pada kategori ini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @foreach ($visibleItems as $item)
        @php $data = $item['data']; @endphp
        <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="approval-{{ $item['type'] }}-{{ $item['id'] }}" role="presentation">
            <section class="w-[min(900px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                <header class="flex min-h-[83px] items-start justify-between gap-5 border-b border-[#edf1f5] px-[22px] py-[17px]">
                    <div>
                        <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]">Tinjau Pengajuan #{{ $item['id'] }}</h2>
                        <p class="m-0 mt-1 text-[10px] font-bold text-[#146bb5]">{{ $typeLabels[$item['type']] }}</p>
                    </div>
                    <button class="inline-flex h-[34px] w-[34px] shrink-0 items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup modal"><svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg></button>
                </header>

                <div class="grid gap-[17px] p-[22px]">
                    @if ($item['type'] === 'loan')
                        @include('persetujuan.partials.loan-detail', compact('data', 'item', 'formatDate', 'formatDateTime', 'statusClass'))
                    @elseif ($item['type'] === 'procurement')
                        @include('persetujuan.partials.procurement-detail', compact('data', 'item', 'rupiah', 'formatDateTime', 'statusClass'))
                    @else
                        @include('persetujuan.partials.disposal-detail', compact('data', 'item', 'formatDateTime', 'statusClass'))
                    @endif

                    @include('persetujuan.partials.approval-timeline', ['item' => $item, 'formatDateTime' => $formatDateTime])

                    @if ($item['can_decide'])
                        @php
                            $action = match ($item['type']) {
                                'loan' => route('web.peminjaman.keputusan', $data),
                                'procurement' => route('web.usulan-pengadaan.keputusan-kasubbag', $data),
                                'disposal' => route('penghapusan-aset.keputusan', $data),
                            };
                            $approvedValue = match ($item['type']) {
                                'procurement' => ProcurementProposalStatus::Approved->value,
                                'loan' => $currentRole === UserRole::TimAset->value ? LoanStatus::VerifiedByAssetTeam->value : LoanStatus::Approved->value,
                                default => AssetDisposalStatus::Approved->value,
                            };
                            $rejectedValue = $item['type'] === 'procurement'
                                ? ProcurementProposalStatus::Rejected->value
                                : ($item['type'] === 'loan' ? LoanStatus::Rejected->value : AssetDisposalStatus::Rejected->value);
                        @endphp
                        <form class="grid gap-3 rounded-[12px] border border-[#dfe7f0] bg-white p-4" method="POST" action="{{ $action }}" data-submit-loading>
                            @csrf
                            @method('PATCH')
                            @if ($item['type'] === 'procurement')
                                <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                    <span>Catatan Kasubbag</span>
                                    <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="catatan" placeholder="Catatan persetujuan (opsional)"></textarea>
                                </label>
                            @endif
                            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Alasan penolakan</span>
                                <textarea class="min-h-20 rounded-[9px] border border-[#dce4ec] px-3 py-3 text-[10px] font-normal outline-0 focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_penolakan" placeholder="Wajib diisi jika pengajuan ditolak"></textarea>
                            </label>
                            <div class="flex flex-wrap justify-end gap-2">
                                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" name="decision" value="{{ $rejectedValue }}" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Tolak</span></button>
                                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" name="decision" value="{{ $approvedValue }}">Setujui</button>
                            </div>
                        </form>
                    @else
                        <div class="rounded-[11px] border border-[#d8e8fb] bg-[#f3f8ff] p-4 text-[11px] font-semibold leading-relaxed text-[#2d638f]">
                            Data ini berstatus {{ $item['status_label'] }}. Tombol Setujui/Tolak hanya muncul saat pengajuan menunggu keputusan role Anda.
                        </div>
                        <div class="flex justify-end border-t border-[#edf1f5] pt-[18px]">
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button>
                        </div>
                    @endif
                </div>
            </section>
        </div>

        @if ($canDeleteApprovals)
            <div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="delete-approval-{{ $item['type'] }}-{{ $item['id'] }}" role="presentation">
                <section class="w-[min(440px,100%)] rounded-2xl bg-white p-7 text-center shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true">
                    <div class="mx-auto mb-4 grid h-[54px] w-[54px] place-items-center rounded-full bg-[#fee2e2] text-[#c94c45]">
                        <svg class="h-7 w-7 fill-none stroke-current stroke-[2.2] [stroke-linecap:round] [stroke-linejoin:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3" /></svg>
                    </div>
                    <h2 class="m-0 text-[20px] font-extrabold text-[#0b2b50]">Hapus data persetujuan?</h2>
                    <p class="mx-auto mb-5 mt-2 max-w-[340px] text-[12px] leading-relaxed text-[#66758a]">
                        Data <strong>{{ $typeLabels[$item['type']] }}</strong> untuk <strong>{{ $item['title'] }}</strong> akan dihapus dari daftar persetujuan.
                    </p>
                    <form class="border-t border-[#edf1f5] pt-5" method="POST" action="{{ route('persetujuan.destroy', ['type' => $item['type'], 'id' => $item['id']]) }}" data-submit-loading>
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="jenis" value="{{ $selectedType }}">
                        <div class="flex justify-center gap-2">
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Batal</button>
                            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94c45] px-3.5 text-[10px] font-bold text-white transition hover:bg-[#a63d37]" type="submit" data-loading-button data-loading-text="Menghapus..."><span data-loading-label>Hapus</span></button>
                        </div>
                    </form>
                </section>
            </div>
        @endif
    @endforeach
    @include('components.image-preview-modal')
</main>
@endsection
