@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="notice notice--error">{{ session('error') }}</div>
    @endif

    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.document-templates.index') }}">
        <div class="acss-crud-head acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <h1 class="acss-page-title">List Dokumen Final</h1>
                <p id="count-text" class="acss-muted">{{ $templates->total() }} template ditemukan.</p>
            </div>
            <div class="acss-crud-head__actions">
                <a class="button button--inline" href="{{ route('kaprodi.document-templates.create') }}">+ Tambah</a>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="filter-form" method="GET" action="{{ route('kaprodi.document-templates.index') }}">
                <input type="hidden" name="sort" id="sort-input" value="{{ $sort }}">
                <input type="hidden" name="direction" id="direction-input" value="{{ $direction }}">
                <label class="form-field acss-search-field">
                    <span>Cari Template</span>
                    <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari nama template...">
                </label>
            </form>

            <div id="table-wrapper">@include('kaprodi.document-templates.partials.table', ['templates' => $templates, 'sort' => $sort, 'direction' => $direction])</div>
            <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.document-templates.partials.pagination', ['templates' => $templates])</div>
        </div>
    </section>

    @include('partials.ajax-list-script', [
        'rootId' => 'list-root',
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
