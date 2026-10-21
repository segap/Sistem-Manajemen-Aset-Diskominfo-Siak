@extends('layouts.app')

@section('title', 'Pemeliharaan Aset - SIMIVA')

@php
    use App\Enums\AssetCondition;
    use App\Enums\AssetMaintenancePriority;
    use App\Enums\AssetMaintenanceStatus;
    use App\Enums\AssetMaintenanceType;
    use App\Enums\MaintenanceFinalResult;
    use App\Enums\MaintenanceMainFunctionStatus;
    use App\Enums\MaintenanceTestResult;
    use Illuminate\Support\Facades\Storage;

    $role = auth()->user()?->role;
    $canSubmit = in_array($role, ['admin_bidang', 'tim_aset', 'super_admin'], true);
    $canProcess = in_array($role, ['tim_aset', 'super_admin'], true);
    $canEditMaintenance = $canProcess;
    $numberStart = ($maintenances->currentPage() - 1) * $maintenances->perPage();
    $selectedPerPage = (int) request('per_page', $maintenances->perPage());
    $conditions = collect([AssetCondition::LightDamage, AssetCondition::MediumDamage, AssetCondition::HeavyDamage])->mapWithKeys(fn ($condition) => [$condition->value => $condition->label()]);
    $conditionLabels = collect(AssetCondition::cases())->mapWithKeys(fn ($condition) => [$condition->value => $condition->label()]);
    $priorities = collect(AssetMaintenancePriority::cases())->mapWithKeys(fn ($priority) => [$priority->value => $priority->label()]);
    $statuses = collect(AssetMaintenanceStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $maintenanceTypes = collect(AssetMaintenanceType::cases())->mapWithKeys(fn ($type) => [$type->value => $type->label()]);
    $functionStatuses = collect(MaintenanceMainFunctionStatus::cases())->mapWithKeys(fn ($status) => [$status->value => $status->label()]);
    $testResults = collect(MaintenanceTestResult::cases())->mapWithKeys(fn ($result) => [$result->value => $result->label()]);
    $finalResults = collect(MaintenanceFinalResult::cases())->mapWithKeys(fn ($result) => [$result->value => $result->label()]);
    $statusClass = fn ($status) => match ($status instanceof AssetMaintenanceStatus ? $status->value : $status) {
        'ditolak_tim_aset' => 'status-ditolak',
        'selesai', 'disetujui_tim_aset' => 'status-disetujui',
        default => 'status-diajukan',
    };
    $priorityClass = fn ($priority) => match ($priority instanceof AssetMaintenancePriority ? $priority->value : $priority) {
        'tinggi' => 'status-ditolak',
        'sedang' => 'status-diajukan',
        'rendah' => 'status-disetujui',
        default => 'status-dikembalikan',
    };
    $rupiah = fn ($value) => $value ? 'Rp '.number_format((float) $value, 0, ',', '.') : '-';
@endphp

@section('content')
<main class="mx-auto w-full max-w-[1480px] px-[30px] pt-5 pb-[30px] max-[820px]:px-[18px] max-[820px]:py-4">
    <div class="mb-6 flex items-end justify-between gap-6 max-[580px]:flex-col max-[580px]:items-stretch">
        <div>
            <h1 class="m-0 mb-[3px] text-[clamp(25px,2.5vw,34px)] font-extrabold tracking-[-.035em] text-[#0b2b50]">Pemeliharaan Aset</h1>
            <p class="m-0 text-[13px] text-[#66758a]">Kelola pengajuan, proses, dan riwayat pemeliharaan aset.</p>
        </div>
        @if ($canSubmit)
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center gap-2 rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f] max-[580px]:w-full" type="button" data-modal-open="create-maintenance"><span class="text-sm font-normal leading-none">+</span> Ajukan Pemeliharaan</button>
        @endif
    </div>

    @if (session('success'))
        <div class="fixed right-6 top-[88px] z-70 flex items-center gap-[9px] rounded-[10px] border border-[#a9e2c9] bg-[#effcf6] px-4 py-3 text-[11px] font-semibold text-[#08714e] shadow-[0_12px_30px_rgb(23_91_65_/_12%)] max-[580px]:left-3 max-[580px]:right-3" role="status" data-auto-dismiss>{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-[10px] border border-[#fecdca] bg-[#fef3f2] px-[15px] py-[13px] text-[11px] text-[#b42318]" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="overflow-hidden rounded-[14px] border border-slate-200 bg-white shadow-[0_8px_25px_rgb(31_62_91_/_4%)]">
        <form class="filter-toolbar flex min-h-[72px] items-center gap-3 border-b border-[#edf1f5] px-[18px] py-3.5 max-[1100px]:flex-wrap" method="GET" action="{{ route('pemeliharaan-aset.index') }}">
            <input class="h-[42px] w-[min(330px,100%)] rounded-[9px] border border-[#dce4ee] bg-[#fbfcfe] px-3 text-[10px] text-[#34445a] outline-0 placeholder:text-[#78879a]" name="search" value="{{ request('search') }}" placeholder="Cari kode, nama aset, pemohon...">
            <select class="h-[42px] min-w-[160px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="kondisi" onchange="this.form.submit()">
                <option value="">Semua kondisi</option>
                @foreach ($conditions as $value => $label)
                    <option value="{{ $value }}" @selected(request('kondisi') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="h-[42px] min-w-[145px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="prioritas" onchange="this.form.submit()">
                <option value="">Semua prioritas</option>
                @foreach ($priorities as $value => $label)
                    <option value="{{ $value }}" @selected(request('prioritas') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <select class="h-[42px] min-w-[180px] rounded-[9px] border border-[#dce4ee] bg-white px-3 text-[10px] font-semibold text-[#34445a] outline-0" name="status" onchange="this.form.submit()">
                <option value="">Semua status</option>
                @foreach ($statuses as $value => $label)
                    <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit">Cari</button>
            @if (request()->hasAny(['search', 'kondisi', 'prioritas', 'status']))
                <a class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ route('pemeliharaan-aset.index') }}">Reset</a>
            @endif
            <x-export-dropdown dataset="pemeliharaan-aset" />
        </form>

        <div class="table-scroll">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="number-column">No</th>
                        <th>Kode Internal</th>
                        <th>Nama Aset</th>
                        <th>Kondisi</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Tanggal Pengajuan</th>
                        <th class="action-column">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maintenances as $index => $maintenance)
                        <tr>
                            <td class="number-column">{{ $numberStart + $index + 1 }}</td>
                            <td><span class="code-badge">{{ $maintenance->asset?->kode_internal ?: '-' }}</span></td>
                            <td><strong>{{ $maintenance->asset?->nama_aset }}</strong><small class="cell-subtitle">{{ $maintenance->applicant?->name ?: '-' }}</small></td>
                            <td>{{ $maintenance->kondisi_sebelum?->label() }}</td>
                            <td><span class="loan-status {{ $priorityClass($maintenance->prioritas) }}">{{ $maintenance->prioritas?->label() ?: '-' }}</span></td>
                            <td><span class="loan-status {{ $statusClass($maintenance->status) }}">{{ $maintenance->status->label() }}</span></td>
                            <td>{{ $maintenance->tanggal_pengajuan?->translatedFormat('d M Y H:i') }}</td>
                            <td class="action-column">
                                <div class="row-actions">
                                    <button type="button" data-modal-open="detail-maintenance-{{ $maintenance->id }}" title="Detail"><svg viewBox="0 0 24 24"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Zm10 3a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" /></svg></button>
                                    @if ($canEditMaintenance)
                                        <button type="button" data-modal-open="edit-maintenance-{{ $maintenance->id }}" title="Edit Pemeliharaan" aria-label="Edit Pemeliharaan {{ $maintenance->id }}"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4L16.5 3.5Z" /></svg></button>
                                    @endif
                                    @if ($maintenance->status === AssetMaintenanceStatus::Completed)
                                        @if ($maintenance->hasCompleteReportData())
                                            <a href="{{ route('pemeliharaan-aset.word', $maintenance) }}" title="Download Word Berita Acara"><svg viewBox="0 0 24 24"><path d="M6 3h9l4 4v14H6V3Zm9 0v5h4M9 13h6m-6 4h6" /></svg></a>
                                        @else
                                            <button type="button" data-modal-open="detail-maintenance-{{ $maintenance->id }}" title="Lengkapi Data Berita Acara"><svg viewBox="0 0 24 24"><path d="M12 3v10m0 4v.01M4 21h16L12 5 4 21Z" /></svg></button>
                                        @endif
                                    @endif
                                    @if ($canEditMaintenance)
                                        <button class="danger" type="button" data-modal-open="delete-maintenance-{{ $maintenance->id }}" title="Hapus Data" aria-label="Hapus Data Pemeliharaan {{ $maintenance->id }}"><svg viewBox="0 0 24 24"><path d="M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" /></svg></button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="table-empty"><strong>Data belum tersedia</strong><p>Belum ada pengajuan pemeliharaan aset.</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-pagination">
            <form class="per-page-control" method="GET" action="{{ route('pemeliharaan-aset.index') }}">
                @foreach (request()->except(['per_page', 'page']) as $key => $value)
                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                @endforeach
                <span>Tampilkan</span>
                <select name="per_page" onchange="this.form.submit()">
                    @foreach ([10, 25, 50, 100] as $option)
                        <option value="{{ $option }}" @selected($selectedPerPage === $option)>{{ $option }} data</option>
                    @endforeach
                </select>
            </form>
            <span>Halaman {{ $maintenances->currentPage() }} dari {{ $maintenances->lastPage() }}</span>
            <div>
                <a class="pagination-icon-button {{ $maintenances->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $maintenances->previousPageUrl() ?: '#' }}"><svg viewBox="0 0 24 24"><path d="M15 18 9 12l6-6" /></svg></a>
                <a class="pagination-icon-button {{ $maintenances->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $maintenances->nextPageUrl() ?: '#' }}"><svg viewBox="0 0 24 24"><path d="m9 18 6-6-6-6" /></svg></a>
            </div>
        </div>
    </section>

    @include('pemeliharaan-aset.partials.create-modal')
    @foreach ($maintenances as $maintenance)
        @include('pemeliharaan-aset.partials.detail-modal', ['timeline' => $histories[$maintenance->id] ?? []])
        @include('pemeliharaan-aset.partials.edit-modal')
        @if ($canEditMaintenance)
            @include('pemeliharaan-aset.partials.delete-modal')
        @endif
    @endforeach
</main>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const errorClass = 'mt-1 text-[10px] font-semibold text-[#b42318]';

            const clearFileError = (input) => {
                input.classList.remove('border-[#f04438]');
                input.parentElement?.querySelector('[data-file-size-error]')?.remove();
            };

            const showFileError = (input, message) => {
                clearFileError(input);
                input.value = '';
                input.classList.add('border-[#f04438]');

                const error = document.createElement('span');
                error.dataset.fileSizeError = 'true';
                error.className = errorClass;
                error.textContent = message;
                input.insertAdjacentElement('afterend', error);
            };

            const validateInput = (input) => {
                const maxSize = Number(input.dataset.maxFileSize || 0);
                if (!maxSize || !input.files?.length) {
                    clearFileError(input);
                    return true;
                }

                const invalidFile = Array.from(input.files).find((file) => file.size > maxSize);
                if (!invalidFile) {
                    clearFileError(input);
                    return true;
                }

                showFileError(input, input.dataset.maxFileMessage || 'gambar/file lebih dari 2mb');
                return false;
            };

            document.addEventListener('change', (event) => {
                const input = event.target.closest('input[type="file"][data-max-file-size]');
                if (input) validateInput(input);
            });

            document.addEventListener('submit', (event) => {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;

                const invalid = Array.from(form.querySelectorAll('input[type="file"][data-max-file-size]'))
                    .filter((input) => !validateInput(input));

                if (invalid.length > 0) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    invalid[0].focus();
                }
            }, true);
        });
    </script>
@endonce
@endsection
