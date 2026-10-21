@php
    use App\Enums\LoanStatus;
    use App\Enums\UserRole;
    use App\Enums\WorkUnit;
    use Illuminate\Support\Facades\Storage;

    $isOverdue = $loan->status === LoanStatus::Borrowed && today()->isAfter($loan->tanggal_kembali);
    $requesterWorkUnit = WorkUnit::labelFor($loan->requester?->work_unit);
    $loanLetterUrl = $loan->surat_peminjaman ? Storage::disk('public')->url($loan->surat_peminjaman) : null;
    $loanLetterName = $loan->nama_surat_peminjaman ?: ($loan->surat_peminjaman ? basename($loan->surat_peminjaman) : null);
    $approval = app(\App\Services\RiwayatPersetujuanService::class)->statusSummary('loan', $loan);
    $timelineItem = ['approval' => $approval];
    $currentRole = auth()->user()?->role instanceof UserRole ? auth()->user()->role->value : auth()->user()?->role;
    $canDecideLoan = $canApproveLoan && (
        ($currentRole === UserRole::TimAset->value && $loan->status === LoanStatus::Submitted)
        || ($currentRole === UserRole::KasubbagUmumPerencanaan->value && $loan->status === LoanStatus::VerifiedByAssetTeam)
    );
    $approveValue = $currentRole === UserRole::TimAset->value ? LoanStatus::VerifiedByAssetTeam->value : LoanStatus::Approved->value;
    $approveLabel = $currentRole === UserRole::TimAset->value ? 'Verifikasi Tim Aset' : 'Setujui Kasubbag';
    $loanDetails = $loan->details->isNotEmpty() ? $loan->details : collect([(object) ['id' => 'legacy-'.$loan->id, 'asset' => $loan->asset, 'kondisi_kembali' => $loan->kondisi_kembali, 'catatan_pengembalian' => $loan->catatan_pengembalian]]);
@endphp

<div class="fixed inset-0 z-80 hidden place-items-center overflow-y-auto bg-[rgb(5_22_38_/_48%)] p-6 animate-[modalBackdropIn_.2s_ease-out_both] max-[580px]:p-3" data-modal="detail-loan-{{ $loan->id }}" role="presentation">
    <section class="w-[min(820px,100%)] max-h-[calc(100vh-48px)] overflow-y-auto rounded-2xl bg-white shadow-[0_25px_80px_rgb(2_19_34_/_28%)] animate-[modalZoomIn_.24s_cubic-bezier(.2,.8,.2,1)_both]" role="dialog" aria-modal="true" aria-labelledby="loan-detail-title-{{ $loan->id }}">
        <header class="flex min-h-[83px] items-center justify-between border-b border-[#edf1f5] px-[22px] py-[17px]">
            <div>
                <h2 class="m-0 mt-[3px] text-xl font-extrabold text-[#0b2b50]" id="loan-detail-title-{{ $loan->id }}">Peminjaman #{{ $loan->id }}</h2>
            </div>
            <button class="inline-flex h-[34px] w-[34px] items-center justify-center rounded-[9px] border-0 bg-[#f2f5f8] text-[#8291a2]" type="button" data-modal-close aria-label="Tutup">
                <svg class="h-[18px] w-[18px] fill-none stroke-current stroke-[2.4] [stroke-linecap:round]" viewBox="0 0 24 24" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
        </header>

        <div class="grid gap-[17px] p-[22px]">
            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Ringkasan pengajuan</h3>
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                @include('peminjaman.partials.detail-item', ['label' => 'Nomor pengajuan', 'value' => 'Peminjaman #'.$loan->id])
                <div class="grid min-h-[67px] gap-[5px] bg-white p-[13px]">
                    <span class="text-[9px] uppercase text-[#8896a7]">Status</span>
                    <strong class="text-[11px] font-extrabold leading-relaxed text-[#33485d]">
                        <span class="loan-status {{ $statusClass($loan->status) }}">{{ $approval['label'] }}</span>
                        @if ($isOverdue)
                            <small class="overdue-label">Terlambat</small>
                        @endif
                    </strong>
                </div>
                @include('peminjaman.partials.detail-item', ['label' => 'Jumlah barang', 'value' => $loan->itemCount().' unit'])
                @include('peminjaman.partials.detail-item', ['label' => 'Tanggal pengajuan', 'value' => $formatDateTime($loan->created_at)])
            </div>

            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Detail peminjam</h3>
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                @include('peminjaman.partials.detail-item', ['label' => 'Nama peminjam', 'value' => $loan->requester->name])
                @include('peminjaman.partials.detail-item', ['label' => 'Unit Kerja', 'value' => $requesterWorkUnit])
                @include('peminjaman.partials.detail-item', ['label' => 'NIP', 'value' => $loan->requester->nip ?: '-'])
            </div>

            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Daftar barang dipinjam</h3>
            <div class="overflow-x-auto rounded-[11px] border border-[#e3e9f0]">
                <table class="w-full min-w-[620px] border-collapse text-left text-[10px]">
                    <thead class="bg-[#f8fafc] text-[9px] uppercase text-[#7c8da3]">
                        <tr>
                            <th class="px-3 py-2">No</th>
                            <th class="px-3 py-2 text-center">Foto Barang</th>
                            <th class="px-3 py-2">Kode Internal</th>
                            <th class="px-3 py-2">Nama Barang</th>
                            <th class="px-3 py-2">Kondisi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#edf1f5]">
                        @foreach ($loanDetails as $detail)
                            @php($asset = $detail->asset)
                            <tr>
                                <td class="px-3 py-2 font-bold text-[#405267]">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 text-center">@include('peminjaman.partials.asset-photo-thumbnail', ['asset' => $asset])</td>
                                <td class="px-3 py-2"><span class="code-badge">{{ $asset?->display_code ?: '-' }}</span></td>
                                <td class="px-3 py-2"><strong>{{ $asset?->nama_aset ?: '-' }}</strong><small class="cell-subtitle">{{ $asset?->merek_tipe ?: 'Merek/tipe belum dicatat' }}</small></td>
                                <td class="px-3 py-2">{{ $asset?->kondisi?->label() ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Jadwal peminjaman</h3>
            <div class="grid grid-cols-2 gap-px overflow-hidden rounded-[11px] border border-[#e3e9f0] bg-[#e3e9f0] max-[580px]:grid-cols-1">
                @include('peminjaman.partials.detail-item', ['label' => 'Tanggal pinjam', 'value' => $formatDate($loan->tanggal_pinjam)])
                @include('peminjaman.partials.detail-item', ['label' => 'Tanggal kembali rencana', 'value' => $formatDate($loan->tanggal_kembali)])
            </div>

            @include('peminjaman.partials.detail-note', ['title' => 'Alasan peminjaman', 'body' => $loan->alasan ?: 'Tanpa alasan.'])

            <div class="rounded-[11px] border border-[#e3e9f0] bg-white p-[13px]">
                <span class="mb-[7px] block text-[9px] font-semibold uppercase text-[#8896a7]">Surat Peminjaman</span>
                @if ($loanLetterUrl)
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <strong class="break-all text-[11px] font-extrabold leading-relaxed text-[#33485d]">{{ $loanLetterName }}</strong>
                        <a class="page-action-button inline-flex min-h-[34px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3 text-[10px] font-bold text-[#146bb5] no-underline transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" href="{{ $loanLetterUrl }}" target="_blank" rel="noopener noreferrer">Lihat Surat</a>
                    </div>
                @else
                    <p class="m-0 text-[11px] font-semibold leading-relaxed text-[#718095]">Surat peminjaman tidak dilampirkan.</p>
                @endif
            </div>

            @include('persetujuan.partials.approval-timeline', ['item' => $timelineItem, 'formatDateTime' => $formatDateTime])

            @if ($loan->alasan_penolakan)
                @include('peminjaman.partials.detail-note', ['title' => 'Alasan penolakan', 'body' => $loan->alasan_penolakan, 'danger' => true])
            @endif

            @if ($loan->status === LoanStatus::Returned)
                <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Hasil pengembalian barang</h3>
                <div class="grid gap-2">
                    @foreach ($loanDetails as $detail)
                        @php($asset = $detail->asset)
                        <div class="rounded-[11px] border border-[#e3e9f0] bg-white p-3 text-[11px]">
                            <strong class="block text-[#0b2b50]">{{ $asset?->display_code ?: '-' }} - {{ $asset?->nama_aset ?: '-' }}</strong>
                            <span class="mt-1 block text-[#718095]">Kondisi kembali: {{ $detail->kondisi_kembali?->label() ?: '-' }}</span>
                            <p class="m-0 mt-1 text-[#718095]">{{ $detail->catatan_pengembalian ?: 'Tanpa catatan.' }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($canDecideLoan)
                <form class="grid gap-3 rounded-[12px] border border-[#e3e9f0] bg-white p-4" method="POST" action="{{ route('web.peminjaman.keputusan', $loan) }}" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                        <span>Alasan penolakan (wajib jika ditolak)</span>
                        <textarea class="h-auto min-h-24 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="alasan_penolakan" rows="3">{{ old('alasan_penolakan') }}</textarea>
                        @error('alasan_penolakan') <small class="text-[8px] font-medium text-[#b8433b]">{{ $message }}</small> @enderror
                    </label>
                    <div class="flex justify-end gap-2">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#c94e47] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(201_78_71_/_16%)] transition hover:bg-[#ad3c36]" type="submit" name="decision" value="ditolak" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Tolak</span></button>
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" name="decision" value="{{ $approveValue }}">{{ $approveLabel }}</button>
                    </div>
                </form>
            @elseif ($canApproveLoan)
                <div class="rounded-[12px] border border-[#d8e8fb] bg-[#f3f8ff] p-4 text-[11px] font-semibold leading-relaxed text-[#2d638f]">
                    Pengajuan ini sudah berstatus {{ $approval['label'] }}. Tombol Setujui/Tolak hanya muncul saat pengajuan menunggu keputusan role Anda.
                </div>
            @endif

            @if (($canHandOverLoan ?? false) && $loan->status->isApprovedForHandover())
                <form class="grid gap-3 rounded-[12px] border border-[#e3e9f0] bg-white p-4" method="POST" action="{{ route('web.peminjaman.penyerahan', $loan) }}" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <p class="m-0 text-[11px] text-[#718095]">Pastikan barang dan penerima sudah sesuai sebelum serah terima.</p>
                    <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Konfirmasi Serah Terima</span></button>
                </form>
            @endif

            @if ($canReturnLoan && $loan->status === LoanStatus::Borrowed)
                <form class="grid gap-3 rounded-[12px] border border-[#e3e9f0] bg-white p-4" method="POST" action="{{ route('web.peminjaman.pengembalian', $loan) }}" data-submit-loading>
                    @csrf
                    @method('PATCH')
                    <h3 class="m-0 text-[11px] font-extrabold text-[#0b2b50]">Data pengembalian per barang</h3>
                    @foreach ($loan->details as $detail)
                        <div class="grid gap-3 rounded-[11px] border border-[#dce4ec] bg-[#f8fafc] p-3">
                            <strong class="text-[11px] text-[#0b2b50]">{{ $detail->asset?->display_code }} - {{ $detail->asset?->nama_aset }}</strong>
                            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Kondisi saat kembali</span>
                                <select class="h-[42px] w-full rounded-[9px] border border-[#dce4ec] bg-white px-3 text-[10px] font-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="details[{{ $detail->id }}][kondisi_kembali]" required>
                                    @foreach ($conditionOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old("details.{$detail->id}.kondisi_kembali", 'baik') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-[7px] text-[11px] font-bold text-[#405267]">
                                <span>Catatan pengembalian</span>
                                <textarea class="h-auto min-h-20 w-full resize-y rounded-[9px] border border-[#dce4ec] bg-white px-3 py-3 text-[10px] font-normal leading-normal text-[#34465a] outline-0 transition focus:border-[#6fa9cf] focus:shadow-[0_0_0_3px_rgb(20_107_181_/_8%)]" name="details[{{ $detail->id }}][catatan_pengembalian]" rows="2">{{ old("details.{$detail->id}.catatan_pengembalian") }}</textarea>
                            </label>
                        </div>
                    @endforeach
                    <div class="flex justify-end">
                        <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border-0 bg-[#146bb5] px-3.5 text-[10px] font-bold text-white shadow-[0_8px_18px_rgb(20_107_181_/_16%)] transition hover:bg-[#0c568f]" type="submit" data-loading-button data-loading-text="Memproses..."><span data-loading-label>Konfirmasi Pengembalian</span></button>
                    </div>
                </form>
            @endif

            <footer class="mt-[5px] flex justify-end border-t border-[#edf1f5] pt-[18px]">
                <button class="page-action-button inline-flex min-h-[38px] items-center justify-center rounded-[9px] border border-[#dbe3eb] bg-white px-3.5 text-[10px] font-bold text-[#5e7084] transition hover:border-[#b8c6d6] hover:bg-[#f8fafc]" type="button" data-modal-close>Tutup</button>
            </footer>
        </div>
    </section>
</div>
