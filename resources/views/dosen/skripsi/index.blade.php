@extends('layouts.app')
@section('content')
    @php
        $taType = $taType ?? (request()->routeIs('dosen.non-skripsi.*') ? 'non_skripsi' : 'skripsi');
        $taLabel = $taType === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi';
        $taRoutePrefix = $taType === 'non_skripsi' ? 'dosen.non-skripsi' : 'dosen.skripsi';
    @endphp
    <section class="acss-crud-card" id="skripsi-list-root" data-endpoint="{{ route($taRoutePrefix.'.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">{{ $taLabel }} Mahasiswa Bimbingan</h1>
                <p id="skripsi-count-text" class="acss-muted ">{{ $skripsis->total() }} {{ str($taLabel)->lower() }} ditemukan.</p>
            </div>
        </div>
        <div class="acss-crud-body">
            <div id="skripsi-stats-wrapper">@include('dosen.skripsi.partials.stats', ['chartData' => $chartData])</div>

            <form class="filter-bar" id="skripsi-filter-form" method="GET" action="{{ route($taRoutePrefix.'.index') }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" name="direction" value="{{ $direction ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Mahasiswa/Judul</span>
                    <input id="skripsi-search-input" type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari NIM, nama, atau judul">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Filter Fase</span>
                    <select name="fase" id="skripsi-fase-select">
                        <option value="all" {{ ($fase ?? 'all') === 'all' ? 'selected' : '' }}>Semua Fase</option>
                        <option value="proposal" {{ ($fase ?? 'all') === 'proposal' ? 'selected' : '' }}>Proposal</option>
                        <option value="sidang_proposal" {{ ($fase ?? 'all') === 'sidang_proposal' ? 'selected' : '' }}>Sidang Proposal</option>
                        <option value="bimbingan_skripsi" {{ ($fase ?? 'all') === 'bimbingan_skripsi' ? 'selected' : '' }}>Bimbingan {{ $taLabel }}</option>
                        <option value="sidang_skripsi" {{ ($fase ?? 'all') === 'sidang_skripsi' ? 'selected' : '' }}>Sidang {{ $taLabel }}</option>
                        <option value="revisi_sidang_skripsi" {{ ($fase ?? 'all') === 'revisi_sidang_skripsi' ? 'selected' : '' }}>Revisi Sidang {{ $taLabel }}</option>
                        <option value="review_dokumen_final" {{ ($fase ?? 'all') === 'review_dokumen_final' ? 'selected' : '' }}>Review Final</option>
                        <option value="skripsi_selesai" {{ ($fase ?? 'all') === 'skripsi_selesai' ? 'selected' : '' }}>{{ $taLabel }} Selesai</option>
                    </select>
                </label>
            </form>
            <div id="skripsi-table-wrapper">@include('dosen.skripsi.partials.table', ['skripsis' => $skripsis, 'sort' => $sort, 'direction' => $direction, 'taType' => $taType])</div>
            <div id="skripsi-pagination-wrapper">@include('dosen.skripsi.partials.pagination', ['skripsis' => $skripsis])</div>
        </div>
    </section>
    @include('partials.ajax-list-script', ['rootId'=>'skripsi-list-root','formId'=>'skripsi-filter-form','searchInputId'=>'skripsi-search-input','statusSelectId'=>'skripsi-fase-select','tableWrapperId'=>'skripsi-table-wrapper','paginationWrapperId'=>'skripsi-pagination-wrapper','statsWrapperId'=>'skripsi-stats-wrapper','countTextId'=>'skripsi-count-text'])
@endsection
