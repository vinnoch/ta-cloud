@extends('layouts.app')

@section('content')
    @php
        $averageScore = $grades->whereNotNull('score')->avg('score');
        $groupedGrades = $grades->groupBy('grade_event');
        $proposalAverage = optional($groupedGrades->get('sidang_proposal', collect()))->whereNotNull('score')->avg('score');
        $skripsiAverage = optional($groupedGrades->get('sidang_skripsi', collect()))->whereNotNull('score')->avg('score');
    @endphp
    <section class="card card--profile mb-4">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $skripsi->student->name ?? 'Mahasiswa' }}</h2>
                        <p>{{ $skripsi->student->nim ?? '-' }} • {{ $skripsi->periode?->name ?? ($skripsi->periode?->kode_periode ?? '-') }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title ?: 'Tanpa Judul' }}</div>
                    </div>
                    <div class="acss-score-stack-group">
                        <div class="acss-score-stack">
                            <span class="pill pill--score-circle pill--score-circle-neutral">{{ $proposalAverage !== null ? rtrim(rtrim(number_format((float) $proposalAverage, 2, '.', ''), '0'), '.') : '-' }}</span>
                            <small class="acss-score-stack__label">Proposal</small>
                        </div>
                        <div class="acss-score-stack">
                            <span class="pill pill--score-circle pill--score-circle-neutral">{{ $skripsiAverage !== null ? rtrim(rtrim(number_format((float) $skripsiAverage, 2, '.', ''), '0'), '.') : '-' }}</span>
                            <small class="acss-score-stack__label">Skripsi</small>
                        </div>
                    </div>
                </div>
                <div class="form-actions form-actions--inline">
                    
                </div>
            </div>
        </div>
    </section>

    @if (($proposalFinalSubmission['allowed'] ?? false) || ($skripsiFinalSubmission['allowed'] ?? false))
        <section class="acss-section-card mb-4">
            <div class="acss-section-card__head">
                <div>
                    <h3 class="acss-card-title">Final Submission Tersedia</h3>
                    <p class="acss-muted mt-1">Lanjutkan pengiriman dokumen final sesuai tahap yang sudah selesai dinilai.</p>
                </div>
            </div>
            <div class="acss-section-card__body">
                <div class="form-actions form-actions--inline">
                    @if ($proposalFinalSubmission['allowed'] ?? false)
                        <a class="button button--inline" href="{{ route('mahasiswa.final.index', [$skripsi, 'sidang_proposal']) }}">Final Proposal</a>
                    @endif
                    @if ($skripsiFinalSubmission['allowed'] ?? false)
                        <a class="button button--inline" href="{{ route('mahasiswa.final.index', [$skripsi, 'sidang_skripsi']) }}">Final Skripsi</a>
                    @endif
                </div>
            </div>
        </section>
    @endif

    @forelse ($groupedGrades as $event => $eventGrades)
        @php
            $eventAverage = $eventGrades->whereNotNull('score')->avg('score');
            $eventName = str($event)->replace('_', ' ')->title();
        @endphp
        <section class="acss-section-card mb-4">
            <div class="acss-section-card__head">
                <div>
                    <h3 class="acss-card-title">Nilai {{ $eventName }}</h3>
                </div>
            </div>
            <div class="acss-section-card__body">
                <div class="table-shell">
                    <div class="table-shell__head table-shell__grid acss-table-cols-mhs-nilai-summary">
                        <span>Dosen</span>
                        <span>Nilai</span>
                    </div>
                    @foreach ($eventGrades as $grade)
                        <div class="table-shell__row table-shell__grid acss-table-cols-mhs-nilai-summary">
                            <div class="table-shell__cell">{{ $grade->reviewer?->name ?? '-' }}</div>
                            <div class="table-shell__cell"><strong>{{ $grade->score ?? '-' }}</strong></div>
                        </div>
                    @endforeach
                </div>
                <p class="acss-muted mt-6 pt-2">
                    Rata-rata Nilai: <strong>{{ $eventAverage ? number_format($eventAverage, 1) : '-' }}</strong>
                </p>
            </div>
        </section>
    @empty
        <section class="acss-section-card mt-4">
            <div class="acss-section-card__body">
                <div class="empty-state">Belum ada nilai yang tersedia.</div>
            </div>
        </section>
    @endforelse
@endsection
