@php
    $sort = $sort ?? 'tanggal';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($requests) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-dosen-sidang-reqs">
            <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="status" data-sort-direction="{{ $nextDirection('status') }}">Status <span>{{ $indicator('status') }}</span></button>
        </div>
    @endif
    @forelse ($requests as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-sidang-reqs acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $item->submitted_at?->format('d/m/Y') ?? '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $item->submitted_at?->format('H:i') ?? '' }}</div>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('dosen.skripsi.show', $item->skripsi) }}">@include('partials.icons.eye')<span>Skripsi</span></a>
                </div>
            </div>
            <div class="table-shell__cell"><strong>{{ $item->skripsi?->student?->name ?? '-' }}</strong><small>{{ $item->skripsi?->student?->nim ?? '-' }}</small></div>
            <div class="table-shell__cell table-shell__cell--title">{{ $item->skripsi?->title ?: '-' }}</div>
            <div class="table-shell__cell"><span class="pill">{{ $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Pending') }}</span></div>
        </div>
    @empty
        <div class="empty-state">Belum ada pengajuan sidang skripsi.</div>
    @endforelse
</div>
