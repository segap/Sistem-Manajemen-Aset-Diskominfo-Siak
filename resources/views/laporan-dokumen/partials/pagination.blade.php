<div class="table-pagination">
    <form class="per-page-control" method="GET" action="{{ $paginationRoute }}">
        @foreach (request()->except(['per_page', 'page']) as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
        <span>Tampilkan</span>
        <select name="per_page" aria-label="Jumlah data per halaman" onchange="this.form.submit()">
            @foreach ([10, 25, 50, 100] as $option)
                <option value="{{ $option }}" @selected($paginator->perPage() === $option)>{{ $option }} data</option>
            @endforeach
        </select>
    </form>
    <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
    <div>
        <a class="pagination-icon-button {{ $paginator->onFirstPage() ? 'pointer-events-none opacity-45' : '' }}" href="{{ $paginator->previousPageUrl() ?: '#' }}" aria-label="Halaman sebelumnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 18 9 12l6-6" /></svg>
        </a>
        <a class="pagination-icon-button {{ $paginator->hasMorePages() ? '' : 'pointer-events-none opacity-45' }}" href="{{ $paginator->nextPageUrl() ?: '#' }}" aria-label="Halaman berikutnya">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6" /></svg>
        </a>
    </div>
</div>
