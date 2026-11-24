@extends('layouts.app')

@section('content')
    <section class="card">
        <div class="section-heading"><div><h1 class="acss-page-title">Publish to Library</h1></div></div>
            @include('partials.pagination.single-page', ['total' => count($items ?? []), 'position' => 'top'])
            <div class="table-shell table-shell--format-assigned">
                <div class="table-shell__head table-shell__grid table-shell__grid--format-assigned"><span>Mahasiswa</span><span>Judul Tugas Akhir</span><span>Dokumen</span><span>Status</span></div>
                @foreach(($items ?? []) as $item)
                    <div class="table-shell__row table-shell__grid table-shell__grid--format-assigned">
                        <div class="table-shell__cell"><strong>{{ $item['student'] ?? '-' }}</strong></div>
                        <div class="table-shell__cell table-shell__cell--title">
                            <div>{{ $item['title'] ?? '-' }}</div>
                            <div><span class="pill">{{ $item['type'] ?? '-' }}</span></div>
                        </div>
                        <div class="table-shell__cell">{{ $item['document'] ?? '-' }}</div>
                        <div class="table-shell__cell"><span class="pill">{{ $item['status'] ?? '-' }}</span></div>
                    </div>
                @endforeach
            </div>
            @include('partials.pagination.single-page', ['total' => count($items ?? []), 'position' => 'bottom'])
    </section>
@endsection
