@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.skripsi.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Monitoring Skripsi</h1>
                <p class="acss-muted " id="count-text">{{ $skripsis->total() }} skripsi ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">

        <div id="stats-wrapper">@include('kaprodi.skripsi.partials.stats', ['chartData' => $chartData])</div>

        <form class="filter-bar acss-filter-form-relative" id="filter-form" method="GET" action="{{ route('kaprodi.skripsi.index') }}" autocomplete="off">
                    <input type="hidden" id="sort-input" name="sort" value="{{ $sort ?? '' }}">
                    <input type="hidden" id="direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
            
            <label class="form-field acss-search-field">
                <span>Pencarian</span>
                <input type="search" id="search-input" class="ta-search" name="q" value="{{ $search }}" placeholder="Cari judul TA, nama, atau NIM">
                            </label>
            <label class="form-field">
                <span>Fase</span>
                <select name="status" id="status-select">
                    <option value="">Semua</option>
                    @foreach (['Proposal','Sidang Proposal','Bimbingan Skripsi','Sidang Skripsi','Revisi Sidang Skripsi','Review Dokumen Final','Skripsi Selesai'] as $option)
                        <option value="{{ $option }}" {{ $status === $option ? 'selected' : '' }}>{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            
        </form>

        <div id="table-wrapper">@include('kaprodi.skripsi.partials.table', ['skripsis' => $skripsis, 'sort' => $sort ?? '', 'direction' => $direction ?? 'desc'])</div>
        <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.skripsi.partials.pagination', ['skripsis' => $skripsis])</div>
        </div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'list-root',
    'formId' => 'filter-form',
    'searchInputId' => 'search-input',
    'statusSelectId' => 'status-select',
    'tableWrapperId' => 'table-wrapper',
    'paginationWrapperId' => 'pagination-wrapper',
    'statsWrapperId' => 'stats-wrapper',
    'countTextId' => 'count-text',
])

<script>
(() => {
    const toggleModal = (modal, show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };

    document.addEventListener('click', (event) => {
        const openButton = event.target.closest('[data-status-modal-open]');
        if (openButton) {
            const modal = document.querySelector(`[data-status-modal="${openButton.dataset.statusModalOpen}"]`);
            toggleModal(modal, true);
            const phaseSelect = modal?.querySelector('[data-status-phase-select]');
            const statusSelect = modal?.querySelector('[data-status-value-select]');
            if (phaseSelect && openButton.dataset.statusCurrentPhase) phaseSelect.value = openButton.dataset.statusCurrentPhase;
            if (statusSelect && openButton.dataset.statusCurrentStatus) statusSelect.value = openButton.dataset.statusCurrentStatus;
            return;
        }

        if (event.target.closest('[data-status-modal-close]')) {
            const modal = event.target.closest('.acss-modal');
            toggleModal(modal, false);
        }
    });
})();
</script>

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
