@php
    $sort = $sort ?? 'tanggal';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($skripsis) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-final-review">
            <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="periode" data-sort-direction="{{ $nextDirection('periode') }}">Periode akademik <span>{{ $indicator('periode') }}</span></button>
        </div>
    @endif
    @forelse ($skripsis as $skripsi)
        <div class="table-shell__row table-shell__grid acss-table-cols-final-review acss-hover-row-group">
            <div class="table-shell__cell">
                @php
                    $dateObj = $skripsi->final_submitted_at ?: $skripsi->documentVersions->first()?->created_at;
                @endphp
                <strong>{{ $dateObj ? $dateObj->format('d/m/Y') : '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $dateObj ? $dateObj->format('H:i') : '' }}</div>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $skripsi) }}">@include('partials.icons.eye')<span>Review Dokumen Final</span></a>
                </div>
            </div>
            <div class="table-shell__cell">
                <strong>{{ $skripsi->student?->name ?? '-' }}</strong>
                <small>{{ $skripsi->student?->nim ?? '-' }}</small>
            </div>
            <div class="table-shell__cell table-shell__cell--title">{{ $skripsi->title ?: '-' }}</div>
            <div class="table-shell__cell">{{ $skripsi->periode?->name ?? '-' }}</div>
        </div>
    @empty
        <div class="empty-state">Belum ada dokumen final yang menunggu validasi.</div>
    @endforelse
</div>
