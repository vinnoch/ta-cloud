@extends('layouts.app')

@section('content')
    <section class="acss-crud-card acss-crud-card--topflush" id="proposal-list-root" data-endpoint="{{ route('dosen.proposal.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Proposal</h1>
                <p id="proposal-count-text" class="acss-muted">{{ $proposals->total() }} proposal ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="proposal-filter-form" method="GET" action="{{ route('dosen.proposal.index') }}">
                <input type="hidden" id="proposal-sort-input" name="sort" value="{{ $sort }}">
                <input type="hidden" id="proposal-direction-input" name="direction" value="{{ $direction }}">
                <label class="form-field acss-search-field">
                    <span>Cari Proposal</span>
                    <input id="proposal-search-input" type="search" name="q" value="{{ $search }}" placeholder="Cari NIM, nama, atau judul proposal">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Status</span>
                    <select name="status" id="proposal-status-input">
                        <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Revisi / Tolak</option>
                    </select>
                </label>
            </form>

            <div id="proposal-table-wrapper">@include('dosen.proposal.partials.table', compact('proposals', 'sort', 'direction'))</div>
            <div id="proposal-pagination-wrapper">@include('dosen.proposal.partials.pagination', compact('proposals'))</div>
        </div>
    </section>

    @include('partials.ajax-list-script', [
        'rootId' => 'proposal-list-root',
        'formId' => 'proposal-filter-form',
        'searchInputId' => 'proposal-search-input',
        'statusSelectId' => 'proposal-status-input',
        'tableWrapperId' => 'proposal-table-wrapper',
        'paginationWrapperId' => 'proposal-pagination-wrapper',
        'countTextId' => 'proposal-count-text',
    ])
@endsection
