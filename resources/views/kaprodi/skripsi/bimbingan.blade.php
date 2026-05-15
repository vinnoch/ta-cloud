@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>Histori Bimbingan</h2>
                        <p>{{ $skripsi->student->name ?? '-' }} • {{ $skripsi->student->nim ?? '-' }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="acss-section-card mt-4">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Riwayat Pertemuan</h3>
            </div>
        </div>
        <div class="acss-section-card__body">
            <style>
                .history-table__row { border-bottom: none !important; }
            </style>
            <div class="history-table">
                <div class="history-table__head history-table__head--four"><span>Tanggal</span><span>Fase</span><span>Dosen</span><span>Catatan</span></div>
                @forelse($bimbingans as $bimbingan)
                    <div class="history-table__row history-table__row--four acss-hover-row-group">
                        <div>
                            <strong>{{ $bimbingan->meeting_date?->format('d/m/Y') ?? '-' }}</strong>
                            <div class="text-[10px] acss-muted">{{ $bimbingan->created_at?->format('H:i') ?? '' }}</div>
                            <div class="acss-row-actions"><a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.bimbingan.show', [$skripsi, $bimbingan]) }}">@include('partials.icons.eye')<span>Detail</span></a></div>
                        </div>
                        <div>{{ str($bimbingan->phase)->replace('_', ' ')->title() }}</div>
                        <div>{{ $bimbingan->reviewer?->name ?? '-' }}</div>
                        <div>{{ \Illuminate\Support\Str::limit($bimbingan->lecturer_notes ?: '-', 100) }}</div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada histori bimbingan.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
