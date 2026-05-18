@php
    $sort = $sort ?? 'nim';
    $direction = $direction ?? 'asc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($skripsis) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi">
            <button type="button" class="acss-sort-button" data-sort-column="nim" data-sort-direction="{{ $nextDirection('nim') }}">Mahasiswa <span>{{ $indicator('nim') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Fase <span>{{ $indicator('fase') }}</span></button>
        </div>
    @endif
    @forelse($skripsis as $s)
        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-skripsi acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $s->student->name ?? '-' }}</strong>
                <small>{{ $s->student->nim ?? '-' }}</small>
                <div class="acss-row-actions"><a class="text-link acss-action-link" href="{{ route('dosen.skripsi.show', $s) }}">@include('partials.icons.eye')<span>Skripsi</span></a></div>
            </div>
            <div class="table-shell__cell table-shell__cell--title">{{ $s->title }}</div>
            <div class="table-shell__cell"><span class="pill">{{ str($s->current_phase)->replace('_',' ')->upper() }}</span></div>
        </div>
    @empty
        <div class="empty-state">Belum ada skripsi assignment.</div>
    @endforelse
</div>
