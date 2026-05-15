@extends('layouts.app')

@section('content')
    <section class="acss-page-card mb-4">
        <div class="acss-page-card__body">
            <h1 class="acss-page-title">Dashboard Dosen</h1>
            <p class="acss-muted mt-1">Ringkasan bimbingan, antrian penilaian, dan persetujuan dokumen final.</p>
        </div>
    </section>

    <section class="acss-dashboard-metric-grid mb-4">
        @foreach ($stats as $index => $stat)
            @include('kaprodi.partials.dashboard-stat-card', [
                'label' => $stat['label'],
                'value' => $stat['value'],
                'hint' => $stat['hint'] ?? null,
                'featured' => $index === 0,
                'href' => $stat['href'] ?? null,
            ])
        @endforeach
    </section>

    <section class="acss-section-card mb-4">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Antrian Penilaian</h3>
                <p class="acss-muted mt-1">Skripsi sidang yang menunggu draft/final nilai dari Anda.</p>
            </div>
        </div>
        <div class="acss-section-card__body">
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid acss-table-cols-dosen-dash-grading">
                    <span>Mahasiswa</span><span>Judul Skripsi</span><span>Peran</span>
                </div>
                @forelse ($gradingQueue as $item)
                    <div class="table-shell__row table-shell__grid acss-table-cols-dosen-dash-grading acss-hover-row-group">
                        <div class="table-shell__cell"><strong>{{ $item['student'] }}</strong>
                            <div class="acss-row-actions"><a class="text-link acss-action-link" href="{{ $item['href'] }}">@include('partials.icons.clipboard')<span>Isi Nilai</span></a></div>
                        </div>
                        <div class="table-shell__cell table-shell__cell--title">{{ $item['title'] }}</div>
                        <div class="table-shell__cell"><span class="pill">{{ strtoupper($item['role']) }}</span></div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada antrian penilaian.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
