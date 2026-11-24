@php
    $sort = $sort ?? 'tanggal';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell pilot-table">
    @if (count($requests) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-dosen-sidang-reqs">
            <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Tugas Akhir <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="status" data-sort-direction="{{ $nextDirection('status') }}">Status <span>{{ $indicator('status') }}</span></button>
        </div>
    @endif
    @forelse ($requests as $item)
        @php $itemRoutePrefix = $item->tugasAkhir->type === 'non_skripsi' ? 'dosen.non-skripsi' : 'dosen.skripsi'; @endphp
        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-sidang-reqs acss-hover-row-group pilot-record-card pilot-record-card--dosen-sidang-request">
            <div class="table-shell__cell pilot-record-card__date" data-mobile-label="Tanggal">
                <strong>{{ $item->submitted_at?->format('d/m/Y') ?? '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $item->submitted_at?->format('H:i') ?? '' }}</div>
                <div class="acss-row-actions pilot-record-card__desktop-actions">
                    <a class="text-link acss-action-link" href="{{ route($itemRoutePrefix.'.show', $item->tugasAkhir) }}">@include('partials.icons.eye')<span>Detail</span></a>
                </div>
            </div>
            <div class="table-shell__cell pilot-record-card__identity"><strong>{{ $item->tugasAkhir?->student?->name ?? '-' }}</strong><small>{{ $item->tugasAkhir?->student?->nim ?? '-' }}</small></div>
            <div class="table-shell__cell table-shell__cell--title pilot-record-card__title">
                <div>{{ $item->tugasAkhir?->title ?: '-' }}</div>
                <div><span class="pill">{{ $item->tugasAkhir?->type === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi' }}</span></div>
            </div>
            <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Status"><span class="pill">{{ $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Pending') }}</span></div>
            <div class="pilot-record-card__mobile-actions">
                <a class="pilot-record-card__primary-action" href="{{ route($itemRoutePrefix.'.show', $item->tugasAkhir) }}">@include('partials.icons.eye')<span>Lihat Detail</span></a>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada pengajuan sidang tugas akhir.</div>
    @endforelse
</div>
