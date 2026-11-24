@if ($paginator->total() > 0)
    @php
        $from = $paginator->firstItem();
        $to = $paginator->lastItem();
    @endphp

    <nav class="tacloud-pagination" role="navigation" aria-label="Pagination">
        <p>Showing {{ $from }} to {{ $to }} of {{ $paginator->total() }} results</p>
        <div class="tacloud-pagination__links">
        @if ($paginator->onFirstPage())
            <span class="tacloud-pagination__link is-disabled" aria-disabled="true" aria-label="Previous page">‹</span>
        @else
            <a class="tacloud-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="tacloud-pagination__link is-disabled" aria-disabled="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page === $paginator->currentPage())
                        <span class="tacloud-pagination__link is-current" aria-current="page">{{ $page }}</span>
                    @else
                        <a class="tacloud-pagination__link" href="{{ $url }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a class="tacloud-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">›</a>
        @else
            <span class="tacloud-pagination__link is-disabled" aria-disabled="true" aria-label="Next page">›</span>
        @endif
        </div>
    </nav>
@endif
