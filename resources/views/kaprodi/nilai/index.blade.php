@extends('layouts.app')

@section('content')
    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.nilai.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Nilai</h1>
                <p class="acss-muted mt-1" id="count-text">{{ $data_nilai->total() }} nilai ditemukan.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="filter-form" method="GET" action="{{ route('kaprodi.nilai.index') }}">
                <input type="hidden" name="sort" id="sort-input" value="{{ $sort }}">
                <input type="hidden" name="direction" id="direction-input" value="{{ $direction }}">
                <label class="form-field acss-search-field">
                    <span>Cari Nilai</span>
                    <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari mahasiswa, judul, atau NIM">
                </label>
                
            </form>

            <div id="table-wrapper">@include('kaprodi.nilai.partials.table', ['data_nilai' => $data_nilai, 'sort' => $sort, 'direction' => $direction])</div>
            <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.nilai.partials.pagination', ['data_nilai' => $data_nilai])</div>
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
@endsection
