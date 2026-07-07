@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="card" id="non-skripsi-list-root" data-endpoint="{{ route('kaprodi.non-skripsi.index') }}">
        <div class="section-heading">
            <div>
                <h1 class="acss-page-title">Monitoring Non-Skripsi</h1>
                <p class="acss-muted" id="count-text">{{ $nonSkripsis->total() }} non-skripsi ditemukan.</p>
            </div>
        </div>

                    <form class="filter-bar acss-filter-form-relative" id="filter-form" method="GET" action="{{ route('kaprodi.non-skripsi.index') }}" autocomplete="off">
                <input type="hidden" id="sort-input" name="sort" value="{{ $sort ?? '' }}">
                <input type="hidden" id="direction-input" name="direction" value="{{ $direction ?? 'desc' }}">

                <label class="form-field acss-search-field">
                    <span>Pencarian</span>
                    <input type="search" id="search-input" class="ta-search" name="q" value="{{ $search }}" placeholder="Cari judul, mahasiswa, NIM, atau link artikel">
                </label>
            </form>

            <div id="table-wrapper">@include('kaprodi.non-skripsi.partials.table', ['nonSkripsis' => $nonSkripsis, 'sort' => $sort ?? '', 'direction' => $direction ?? 'desc'])</div>
            <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.non-skripsi.partials.pagination', ['nonSkripsis' => $nonSkripsis])</div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'non-skripsi-list-root',
    'formId' => 'filter-form',
    'searchInputId' => 'search-input',
    'tableWrapperId' => 'table-wrapper',
    'paginationWrapperId' => 'pagination-wrapper',
    'countTextId' => 'count-text',
])

<script>
(() => {
    const form = document.getElementById('filter-form');
    const sortInput = document.getElementById('sort-input');
    const directionInput = document.getElementById('direction-input');
    const tableWrapper = document.getElementById('table-wrapper');
    if (!form || !sortInput || !directionInput || !tableWrapper) return;
    tableWrapper.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sort-column]');
        if (!button) return;
        sortInput.value = button.dataset.sortColumn || '';
        directionInput.value = button.dataset.sortDirection || 'desc';
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });
})();
</script>
@endsection
