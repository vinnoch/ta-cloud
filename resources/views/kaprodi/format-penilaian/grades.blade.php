@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student?->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $skripsi->student?->name ?? '-' }}</h2>
                        <p>{{ $skripsi->student?->nim ?? '-' }} • {{ $skripsi->periode?->name ?? ($skripsi->periode?->kode_periode ?? '-') }}</p>
                    </div>
                    <span class="status-pill">{{ strtoupper($skripsi->current_phase) }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__head">
            <div><h3 class="acss-card-title">Nilai Sidang</h3></div>
        </div>
        <div class="acss-section-card__body">
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid acss-table-cols-grades-unlock">
                    <span>Reviewer</span>
                    <span>Peran</span>
                    <span>Catatan Revisi</span>
                    <span>Status</span>
                    <span>Nilai Akhir</span>
                    <span>Aksi</span>
                </div>
                @forelse($grades as $grade)
                    <div class="table-shell__row table-shell__grid acss-table-cols-grades-unlock acss-hover-row-group">
                        <div class="table-shell__cell">
                            <strong>{{ $grade->reviewer?->name ?? '-' }}</strong>
                            <small>{{ $grade->reviewer?->nidn_nip ?? '-' }}</small>
                        </div>
                        <div class="table-shell__cell">{{ str($grade->role_type)->replace('_', ' ')->title() }}</div>
                        <div class="table-shell__cell">{{ \Illuminate\Support\Str::limit($grade->notes ?: '-', 90) }}</div>
                        <div class="table-shell__cell"><span class="pill {{ $grade->locked_at ? 'pill--blue' : 'pill--muted' }}">{{ $grade->locked_at ? 'LOCKED' : strtoupper($grade->status) }}</span></div>
                        <div class="table-shell__cell">{{ ! is_null($grade->score) ? number_format((float) $grade->score, 2) : '-' }}</div>
                        <div class="table-shell__cell">
                            @if ($grade->locked_at && $grade->unlock_requested_at)
                                <form method="POST" action="{{ route('kaprodi.formats.grades.unlock', [$format, $skripsi, $grade]) }}">
                                    @csrf
                                    <button type="submit" class="text-link acss-action-link">@include('partials.icons.settings')<span>Buka Kunci Nilai</span></button>
                                </form>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada data nilai.</div>
                @endforelse
            </div>
        </div>
    </section>

    @if ($skripsi->current_phase === 'review_dokumen_final')
        <section class="acss-section-card">
            <div class="acss-section-card__head"><div><h3 class="acss-card-title">Status Approval Dokumen Final</h3></div></div>
            <div class="acss-section-card__body">
                <div class="table-shell">
                    <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi-approval">
                        <span>Reviewer</span>
                        <span>Peran</span>
                        <span>Status</span>
                    </div>
                    @forelse($finalApprovals as $approval)
                        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-skripsi-approval acss-hover-row-group">
                            <div class="table-shell__cell"><strong>{{ $approval->reviewer?->name ?? '-' }}</strong></div>
                            <div class="table-shell__cell">{{ str($approval->role_type)->replace('_', ' ')->title() }}</div>
                            <div class="table-shell__cell"><span class="pill">{{ strtoupper($approval->status) }}</span></div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada status approval final.</div>
                    @endforelse
                </div>
            </div>
        </section>
    @endif
@endsection
