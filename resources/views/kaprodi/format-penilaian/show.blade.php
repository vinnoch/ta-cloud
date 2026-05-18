@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">F</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $format->name }}</h2>
                        <p>{{ $format->periode?->name ?? 'Format Nilai' }} • {{ $format->is_published ? 'Published' : 'Draft' }}</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($format->is_published)
                            <span class="status-pill status-pill--published">PUBLISHED</span>
                        @else
                            <span class="status-pill status-pill--draft">DRAFT</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="acss-inline-actions form-actions form-actions--inline">
            <a href="{{ route('kaprodi.formats.edit', $format) }}" class="button button--muted button--inline">
                <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M13.75 3.75a1.768 1.768 0 1 1 2.5 2.5L7.5 15H5v-2.5l8.75-8.75Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.25 6.25l2.5 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <span>Edit Format Nilai</span>
            </a>
        </div>
    </section>

    <div class="acss-stack-sections">
        <section class="acss-crud-card" id="assigned-list-root" data-endpoint="{{ route('kaprodi.formats.show', $format) }}">
            <div class="acss-crud-head">
                <div>
                    <h3 class="acss-card-title">Nilai Sidang Terhubung</h3>
                </div>
            </div>

            <div class="acss-crud-body">
                <form class="filter-bar" id="assigned-filter-form" method="GET" action="{{ route('kaprodi.formats.show', $format) }}" autocomplete="off">
                    <input type="hidden" id="assigned-sort-input" name="assigned_sort" value="{{ $assignedSort ?? '' }}">
                    <input type="hidden" id="assigned-direction-input" name="assigned_direction" value="{{ $assignedDirection ?? 'desc' }}">
                    
                    <label class="form-field acss-search-field">
                        <span>Cari Mahasiswa</span>
                        <input type="search" id="assigned-search-input" name="assigned_q" value="{{ $assignedSearch ?? '' }}" placeholder="Cari nama atau NIM">
                    </label>
                </form>

                <p id="assigned-count-text" class="acss-muted ">{{ $assignedSkripsis->total() }} skripsi terhubung.</p>
                
                <div id="assigned-table-wrapper">
                    @include('kaprodi.format-penilaian.partials.assigned-table', [
                        'format' => $format, 
                        'assignedSkripsis' => $assignedSkripsis, 
                        'assignedSort' => $assignedSort ?? '', 
                        'assignedDirection' => $assignedDirection ?? 'desc'
                    ])
                </div>

                <div id="assigned-pagination-wrapper" class="acss-pagination-spacer">
                    @include('kaprodi.format-penilaian.partials.assigned-pagination', ['assignedSkripsis' => $assignedSkripsis])
                </div>
            </div>
        </section>
    </div>

@include('partials.ajax-list-script', [
    'rootId' => 'assigned-list-root', 
    'formId' => 'assigned-filter-form', 
    'searchInputId' => 'assigned-search-input', 
    'tableWrapperId' => 'assigned-table-wrapper', 
    'paginationWrapperId' => 'assigned-pagination-wrapper', 
    'countTextId' => 'assigned-count-text'
])

<script>
(() => {
    const form = document.getElementById('assigned-filter-form');
    const sortInput = document.getElementById('assigned-sort-input');
    const directionInput = document.getElementById('assigned-direction-input');
    const tableWrapper = document.getElementById('assigned-table-wrapper');

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
