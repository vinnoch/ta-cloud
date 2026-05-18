@extends('layouts.app')
@section('content')
    <section class="acss-crud-card" id="grade-list-root" data-endpoint="{{ route('dosen.penilaian.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Antrian Penilaian</h1>
                <p id="grade-count-text" class="acss-muted ">{{ $gradingQueue->total() }} antrian ditemukan.</p>
            </div>
        </div>
        <div class="acss-crud-body">
            <form class="filter-bar" id="grade-filter-form" method="GET" action="{{ route('dosen.penilaian.index') }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" name="direction" value="{{ $direction ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Mahasiswa/Judul</span>
                    <input id="grade-search-input" type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari NIM, nama, atau judul">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Nilai Sidang</span>
                    <select name="nilai_sidang" id="grade-nilai-sidang-select">
                        <option value="">Semua</option>
                        <option value="sidang_proposal" {{ ($nilaiSidang ?? '') === 'sidang_proposal' ? 'selected' : '' }}>Sidang Proposal</option>
                        <option value="sidang_skripsi" {{ ($nilaiSidang ?? '') === 'sidang_skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                    </select>
                </label>
            </form>
            <div id="grade-table-wrapper">@include('dosen.penilaian.partials.table', ['gradingQueue' => $gradingQueue, 'sort' => $sort, 'direction' => $direction])</div>
            <div id="grade-pagination-wrapper">@include('dosen.penilaian.partials.pagination', ['gradingQueue' => $gradingQueue])</div>
        </div>
    </section>
    @include('partials.ajax-list-script', ['rootId'=>'grade-list-root','formId'=>'grade-filter-form','searchInputId'=>'grade-search-input','statusSelectId'=>'grade-nilai-sidang-select','tableWrapperId'=>'grade-table-wrapper','paginationWrapperId'=>'grade-pagination-wrapper','countTextId'=>'grade-count-text'])
@endsection
