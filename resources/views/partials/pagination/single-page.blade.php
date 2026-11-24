@php
    $paginationTotal = max(0, (int) ($total ?? 0));
@endphp
@if ($paginationTotal > 0)
    <div class="pagination-shell {{ $position === 'top' ? 'acss-pagination-top' : 'acss-pagination-spacer' }}">
        <nav class="tacloud-pagination" role="navigation" aria-label="Pagination">
            <p>Showing 1 to {{ $paginationTotal }} of {{ $paginationTotal }} results</p>
            <div class="tacloud-pagination__links">
                <span class="tacloud-pagination__link is-disabled" aria-disabled="true" aria-label="Previous page">‹</span>
                <span class="tacloud-pagination__link is-current" aria-current="page">1</span>
                <span class="tacloud-pagination__link is-disabled" aria-disabled="true" aria-label="Next page">›</span>
            </div>
        </nav>
    </div>
@endif
