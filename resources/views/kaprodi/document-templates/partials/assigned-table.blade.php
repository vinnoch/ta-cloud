@php
    $sort = $assignedSort ?? '';
    $direction = $assignedDirection ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp

<div class="table-shell table-shell--format-assigned">
    <div class="table-shell__head table-shell__grid table-shell__grid--format-assigned">
        <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
        <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
        <button type="button" class="acss-sort-button" data-sort-column="periode" data-sort-direction="{{ $nextDirection('periode') }}">Periode <span>{{ $indicator('periode') }}</span></button>
    </div>
    @forelse ($assignedSkripsis as $skripsi)
        <div class="table-shell__row table-shell__grid table-shell__grid--format-assigned acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $skripsi->student?->name ?? '-' }}</strong>
                <small>{{ $skripsi->student?->nim ?? '-' }}</small>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $skripsi) }}">@include('partials.icons.eye')<span>Detail</span></a>
                </div>
            </div>
            <div class="table-shell__cell table-shell__cell--title">{{ $skripsi->title ?: '-' }}</div>
            <div class="table-shell__cell">{{ $skripsi->periode?->name ?? '-' }}</div>
        </div>
    @empty
        <div class="empty-state">Belum ada skripsi yang terhubung dengan template ini.</div>
    @endforelse
</div>
