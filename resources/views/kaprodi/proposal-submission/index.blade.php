@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="proposal-list-root" data-endpoint="{{ route('kaprodi.proposal-submissions.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Pengajuan Proposal</h1>
                <p id="proposal-count-text" class="acss-muted ">{{ $proposals->total() }} pengajuan proposal ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="proposal-filter-form" method="GET" action="{{ route('kaprodi.proposal-submissions.index') }}">
                <input type="hidden" id="proposal-sort-input" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" id="proposal-direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <input type="hidden" name="periode_id" value="{{ ($periodeId ?? 0) > 0 ? $periodeId : '' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Pengajuan</span>
                    <input id="proposal-search-input" type="search" name="q" value="{{ $search }}" placeholder="Cari NIM, nama, atau judul proposal">
                </label>
            </form>

            <div id="proposal-table-wrapper">@include('kaprodi.proposal-submission.partials.table', ['proposals' => $proposals, 'sort' => $sort ?? 'tanggal', 'direction' => $direction ?? 'desc'])</div>
            <div id="proposal-pagination-wrapper">@include('kaprodi.proposal-submission.partials.pagination', ['proposals' => $proposals])</div>
        </div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'proposal-list-root',
    'formId' => 'proposal-filter-form',
    'searchInputId' => 'proposal-search-input',
    'tableWrapperId' => 'proposal-table-wrapper',
    'paginationWrapperId' => 'proposal-pagination-wrapper',
    'countTextId' => 'proposal-count-text',
])
@endsection
