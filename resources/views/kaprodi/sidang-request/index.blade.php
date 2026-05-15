@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="sidang-list-root" data-endpoint="{{ route('kaprodi.sidang-requests.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Permohonan Sidang</h1>
                <p id="sidang-count-text" class="acss-muted mt-1">{{ $requests->total() }} permohonan sidang ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="sidang-filter-form" method="GET" action="{{ route('kaprodi.sidang-requests.index') }}">
                <input type="hidden" id="sidang-sort-input" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" id="sidang-direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <input type="hidden" name="periode_id" value="{{ ($periodeId ?? 0) > 0 ? $periodeId : "" }}">
                <label class="form-field acss-search-field">
                    <span>Cari Permohonan</span>
                    <input id="sidang-search-input" type="search" name="q" value="{{ $search }}" placeholder="Cari mahasiswa atau judul skripsi">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Tipe Sidang</span>
                    <select name="sidang_type">
                        <option value="all" {{ ($sidangType ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="proposal" {{ ($sidangType ?? 'all') === 'proposal' ? 'selected' : '' }}>Proposal</option>
                        <option value="skripsi" {{ ($sidangType ?? 'all') === 'skripsi' ? 'selected' : '' }}>Skripsi</option>
                    </select>
                </label>
                <label class="form-field acss-field-tight">
                    <span>Filter Approval</span>
                    <select name="approval_status">
                        <option value="all" {{ ($approvalStatus ?? 'all') === 'all' ? 'selected' : '' }}>Semua</option>
                        <option value="pending_approval" {{ ($approvalStatus ?? 'all') === 'pending_approval' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ ($approvalStatus ?? 'all') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                        <option value="rejected" {{ ($approvalStatus ?? 'all') === 'rejected' ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </label>
                
            </form>

            <div id="sidang-table-wrapper">@include('kaprodi.sidang-request.partials.table', ['requests' => $requests, 'sort' => $sort ?? 'tanggal', 'direction' => $direction ?? 'desc'])</div>
            <div id="sidang-pagination-wrapper">@include('kaprodi.sidang-request.partials.pagination', ['requests' => $requests])</div>
        </div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'sidang-list-root',
    'formId' => 'sidang-filter-form',
    'searchInputId' => 'sidang-search-input',
    'tableWrapperId' => 'sidang-table-wrapper',
    'paginationWrapperId' => 'sidang-pagination-wrapper',
    'countTextId' => 'sidang-count-text',
])
@endsection
