@extends('layouts.app')

@section('content')
    @php
        $label = $tugasAkhirType === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi';
        $routePrefix = $tugasAkhirType === 'non_skripsi' ? 'superadmin.non-skripsi' : 'superadmin.skripsi';
    @endphp

    <section class="card" id="list-root" data-endpoint="{{ route($routePrefix.'.index') }}">
        <div class="section-heading">
            <div>
                <h1 class="acss-page-title">{{ $label }}</h1>
                <p class="acss-muted" id="count-text">{{ $tugasAkhirs->total() }} {{ str($label)->lower() }} ditemukan.</p>
            </div>
        </div>

        <form class="filter-bar acss-filter-form-relative" id="filter-form" method="GET" action="{{ route($routePrefix.'.index') }}" autocomplete="off">
            <label class="form-field acss-search-field">
                <span>Pencarian</span>
                <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari judul TA, nama, atau NIM">
            </label>
            <label class="form-field">
                <span>Fase</span>
                <select name="status" id="status-select">
                    <option value="">Semua</option>
                    @foreach ($phaseLabels as $value => $phaseLabel)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $phaseLabel }}</option>
                    @endforeach
                </select>
            </label>
        </form>

        <div id="table-wrapper">@include('superadmin.tugas-akhir.partials.table')</div>
        <div id="pagination-wrapper" class="acss-pagination-spacer">@include('superadmin.tugas-akhir.partials.pagination')</div>
    </section>

    @include('partials.ajax-list-script', [
        'rootId' => 'list-root',
        'formId' => 'filter-form',
        'searchInputId' => 'search-input',
        'statusSelectId' => 'status-select',
        'tableWrapperId' => 'table-wrapper',
        'paginationWrapperId' => 'pagination-wrapper',
        'countTextId' => 'count-text',
    ])
@endsection
