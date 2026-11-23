@extends('layouts.app')

@section('content')
    <section class="acss-section-card">
        <div class="acss-section-card__head"><div><h3 class="acss-card-title">Publish to Library</h3></div></div>
        <div class="acss-section-card__body">
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
        </div>
    </section>
@endsection
