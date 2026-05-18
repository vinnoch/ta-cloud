@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="dosen-sidang-list-root" data-endpoint="{{ route('dosen.sidang-request.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Pengajuan Sidang Skripsi</h1>
                <p id="dosen-sidang-count-text" class="acss-muted ">{{ $requests->total() }} pengajuan sidang skripsi ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="dosen-sidang-filter-form" method="GET" action="{{ route('dosen.sidang-request.index') }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" name="direction" value="{{ $direction ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Pengajuan</span>
                    <input id="dosen-sidang-search-input" type="search" name="q" value="{{ $search }}" placeholder="Cari mahasiswa atau judul skripsi">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Status</span>
                    <select name="status" id="dosen-sidang-status-select">
                        <option value="">Semua</option>
                        <option value="submitted" {{ ($status ?? '') === 'submitted' ? 'selected' : '' }}>Diajukan</option>
                        <option value="approved" {{ ($status ?? '') === 'approved' ? 'selected' : '' }}>Disetujui</option>
                    </select>
                </label>
            </form>

            <div id="dosen-sidang-table-wrapper">@include('dosen.sidang-request.partials.table', ['requests' => $requests, 'sort' => $sort ?? 'tanggal', 'direction' => $direction ?? 'desc'])</div>
            <div id="dosen-sidang-pagination-wrapper">@include('dosen.sidang-request.partials.pagination', ['requests' => $requests])</div>
        </div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'dosen-sidang-list-root',
    'formId' => 'dosen-sidang-filter-form',
    'searchInputId' => 'dosen-sidang-search-input',
    'statusSelectId' => 'dosen-sidang-status-select',
    'tableWrapperId' => 'dosen-sidang-table-wrapper',
    'paginationWrapperId' => 'dosen-sidang-pagination-wrapper',
    'countTextId' => 'dosen-sidang-count-text',
])
@endsection
