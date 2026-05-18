@php
    $sort = $assignedSort ?? '';
    $direction = $assignedDirection ?? 'desc';
    $nextDirection = function (string $column) use ($sort, $direction): string {
        return $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    };
    $indicator = function (string $column) use ($sort, $direction): string {
        if ($sort !== $column) return '↕';
        return $direction === 'asc' ? '↑' : '↓';
    };
@endphp

<div class="table-shell table-shell--format-assigned">
    @if (count($assignedSkripsis) > 0)
        <div class="table-shell__head table-shell__grid" style="--table-cols: minmax(0, 0.9fr) minmax(0, 1.4fr) minmax(0, 0.7fr);">
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Fase <span>{{ $indicator('fase') }}</span></button>
        </div>
    @endif
    @forelse ($assignedSkripsis as $skripsi)
        <div class="table-shell__row table-shell__grid acss-hover-row-group" style="--table-cols: minmax(0, 0.9fr) minmax(0, 1.4fr) minmax(0, 0.7fr);">
            <div class="table-shell__cell">
                <strong>{{ $skripsi->student?->name ?? '-' }}</strong>
                <small>{{ $skripsi->student?->nim ?? '-' }}</small>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $skripsi) }}" aria-label="Skripsi">
                        <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M10 4.5C5.5 4.5 2.43 8.11 1.5 10c.93 1.89 4 5.5 8.5 5.5s7.57-3.61 8.5-5.5c-.93-1.89-4-5.5-8.5-5.5Z" stroke="currentColor" stroke-width="1.5"/><circle cx="10" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5"/></svg>
                        <span>Skripsi</span>
                    </a>
                </div>
            </div>
            <div class="table-shell__cell table-shell__cell--title">{{ $skripsi->title ?: '-' }}</div>
            <div class="table-shell__cell">
                <span class="pill pill--blue">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->title() }}</span>
            </div>
        </div>
    @empty
        <div class="empty-state">Tidak ada skripsi aktif.</div>
    @endforelse
</div>
