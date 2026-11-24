@extends('layouts.app')

@section('content')
    <section class="card card--profile acss-skripsi-detail-topcompact">
        <div class="profile-card acss-dashboard-header">
            <div>
                <h1 class="acss-page-title">Dashboard Dosen</h1>
            </div>
        </div>
    </section>

    <section class="acss-dashboard-metric-grid">
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

    <section class="acss-section-card">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Antrian Penilaian</h3>
            </div>
        </div>
        <div class="acss-section-card__body">
            <div class="table-shell pilot-table">
                @if ($gradingQueue->isNotEmpty())
                    <div class="table-shell__head table-shell__grid acss-table-cols-dosen-dash-grading">
                        <span>Mahasiswa</span><span>Judul Tugas Akhir</span><span>Peran</span>
                    </div>
                @endif
                @forelse ($gradingQueue as $item)
                    <div class="table-shell__row table-shell__grid acss-table-cols-dosen-dash-grading acss-hover-row-group pilot-record-card pilot-record-card--dosen-dashboard">
                        <div class="table-shell__cell pilot-record-card__identity"><strong>{{ $item['student'] }}</strong>
                            <div class="acss-row-actions pilot-record-card__desktop-actions"><a class="text-link acss-action-link" href="{{ $item['href'] }}">@include('partials.icons.clipboard')<span>Isi Nilai</span></a></div>
                        </div>
                        <div class="table-shell__cell table-shell__cell--title pilot-record-card__title">
                            <div>{{ $item['title'] }}</div>
                            <div><span class="pill">{{ $item['type_label'] }}</span></div>
                        </div>
                        <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Peran"><span class="pill">{{ strtoupper($item['role']) }}</span></div>
                        <div class="pilot-record-card__mobile-actions">
                            <a class="pilot-record-card__primary-action" href="{{ $item['href'] }}">@include('partials.icons.clipboard')<span>Isi Nilai</span></a>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada antrian penilaian.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
