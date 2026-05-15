@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="final-review-list-root" data-endpoint="{{ route('kaprodi.final-reviews.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Review Dokumen Final</h1>
                <p id="final-review-count-text" class="acss-muted mt-1">{{ $skripsis->total() }} dokumen final ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="final-review-filter-form" method="GET" action="{{ route('kaprodi.final-reviews.index') }}">
                <input type="hidden" id="final-review-sort-input" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" id="final-review-direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <input type="hidden" name="periode_id" value="{{ ($periodeId ?? 0) > 0 ? $periodeId : "" }}">
                <label class="form-field acss-search-field">
                    <span>Cari Dokumen Final</span>
                    <input id="final-review-search-input" type="search" name="q" value="{{ $search }}" placeholder="Cari NIM, nama, atau judul skripsi">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Filter Approval</span>
                    <select name="approval_status">
                        <option value="all" {{ ($approvalStatus ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="pending_approval" {{ ($approvalStatus ?? 'all') === 'pending_approval' ? 'selected' : '' }}>Pending Kaprodi</option>
                        <option value="approved" {{ ($approvalStatus ?? 'all') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    </select>
                </label>
                
            </form>

            <div id="final-review-table-wrapper">@include('kaprodi.final-review.partials.table', ['skripsis' => $skripsis, 'sort' => $sort ?? 'tanggal', 'direction' => $direction ?? 'desc'])</div>
            <div id="final-review-pagination-wrapper">@include('kaprodi.final-review.partials.pagination', ['skripsis' => $skripsis])</div>
        </div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'final-review-list-root',
    'formId' => 'final-review-filter-form',
    'searchInputId' => 'final-review-search-input',
    'tableWrapperId' => 'final-review-table-wrapper',
    'paginationWrapperId' => 'final-review-pagination-wrapper',
    'countTextId' => 'final-review-count-text',
])
@endsection
