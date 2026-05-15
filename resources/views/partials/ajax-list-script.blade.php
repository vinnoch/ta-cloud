@php
    $rootId = $rootId ?? 'list-root';
    $formId = $formId ?? 'filter-form';
    $searchInputId = $searchInputId ?? 'search-input';
    $statusSelectId = $statusSelectId ?? null;
    $tableWrapperId = $tableWrapperId ?? 'table-wrapper';
    $paginationWrapperId = $paginationWrapperId ?? 'pagination-wrapper';
    $statsWrapperId = $statsWrapperId ?? null;
    $countTextId = $countTextId ?? 'count-text';
@endphp

<script>
(() => {
    const root = document.getElementById('{{ $rootId }}');
    if (!root) return;

    const endpoint = root.dataset.endpoint;
    const form = document.getElementById('{{ $formId }}');
    const searchInput = document.getElementById('{{ $searchInputId }}');
    const statusSelect = @if($statusSelectId) document.getElementById('{{ $statusSelectId }}') @else null @endif;
    const tableWrapper = document.getElementById('{{ $tableWrapperId }}');
    const paginationWrapper = document.getElementById('{{ $paginationWrapperId }}');
    const statsWrapper = @if($statsWrapperId) document.getElementById('{{ $statsWrapperId }}') @else null @endif;
    const countText = document.getElementById('{{ $countTextId }}');
    let debounceTimer;

    const syncActiveStatCard = () => {
        if (!statsWrapper || !statusSelect) return;
        const currentStatus = statusSelect.value;
        statsWrapper.querySelectorAll('[data-filter-status]').forEach((card) => {
            card.classList.toggle('is-active', currentStatus !== '' && card.dataset.filterStatus === currentStatus);
        });
    };

    async function fetchData(pageUrl = null) {
        const params = new URLSearchParams();
        const currentSearch = searchInput ? searchInput.value.trim() : '';

        if (form) {
            const formData = new FormData(form);
            for (const [key, value] of formData.entries()) {
                if (value !== null && String(value).trim() !== '') {
                    params.set(key, String(value).trim());
                }
            }
        }

        if (currentSearch !== '') params.set('q', currentSearch);
        else params.delete('q');

        if (pageUrl) {
            const parsed = new URL(pageUrl, window.location.origin);
            const page = parsed.searchParams.get('page');
            if (page) params.set('page', page);
        }

        const browserUrl = params.toString() ? `${endpoint}?${params.toString()}` : endpoint;
        window.history.replaceState({}, '', browserUrl);

        const response = await fetch(browserUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        });

        if (!response.ok) return;
        const payload = await response.json();
        
        if (tableWrapper && payload.table_html) tableWrapper.innerHTML = payload.table_html;
        if (paginationWrapper && payload.pagination_html) paginationWrapper.innerHTML = payload.pagination_html;
        if (statsWrapper && payload.stats_html) statsWrapper.innerHTML = payload.stats_html;
        if (countText && payload.count_text) countText.textContent = payload.count_text;

        if (pageUrl && tableWrapper) {
            const tableHeader = tableWrapper.querySelector('.table-shell__head, .history-table__head');
            const scrollTarget = tableHeader || tableWrapper;
            const tableTop = scrollTarget.getBoundingClientRect().top + window.scrollY - 50;
            window.scrollTo({ top: Math.max(tableTop, 0), behavior: 'smooth' });
        }
        
        syncActiveStatCard();
        @if(isset($onFetchSuccess)) {!! $onFetchSuccess !!} @endif
    }

    if (form) {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            fetchData();
        });
    }

    if (form) {
        form.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => fetchData());
        });
    }

    if (statsWrapper) {
        statsWrapper.addEventListener('click', (event) => {
            const card = event.target.closest('[data-filter-status]');
            if (card && statusSelect) {
                const status = card.dataset.filterStatus;
                statusSelect.value = statusSelect.value === status ? '' : status;
                fetchData();
            }
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchData(), 250);
        });
    }

    if (paginationWrapper) {
        paginationWrapper.addEventListener('click', (event) => {
            const link = event.target.closest('a');
            if (!link) return;
            event.preventDefault();
            fetchData(link.href);
        });
    }

    if (tableWrapper) {
        tableWrapper.addEventListener('click', (event) => {
            const sortButton = event.target.closest('[data-sort-column]');
            if (!sortButton || !form) return;
            event.preventDefault();
            const sortInput = form.querySelector('input[name="sort"]');
            const directionInput = form.querySelector('input[name="direction"]');
            if (sortInput) sortInput.value = sortButton.dataset.sortColumn || '';
            if (directionInput) directionInput.value = sortButton.dataset.sortDirection || 'desc';
            fetchData();
        });
    }

    syncActiveStatCard();
})();
</script>
