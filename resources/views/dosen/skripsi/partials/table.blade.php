@php
    $sort = $sort ?? 'nim';
    $direction = $direction ?? 'asc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
    $taType = $taType ?? (request()->routeIs('dosen.non-skripsi.*') ? 'non_skripsi' : 'skripsi');
    $taLabel = $taType === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi';
@endphp
<div class="table-shell pilot-table pilot-table--dosen-skripsi">
    @if (count($skripsis) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi">
            <button type="button" class="acss-sort-button" data-sort-column="nim" data-sort-direction="{{ $nextDirection('nim') }}">Mahasiswa <span>{{ $indicator('nim') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul {{ $taLabel }} <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Fase <span>{{ $indicator('fase') }}</span></button>
        </div>
    @endif
    @forelse($skripsis as $s)
        @php $itemRoutePrefix = $s->type === 'non_skripsi' ? 'dosen.non-skripsi' : 'dosen.skripsi'; @endphp
        <article class="table-shell__row table-shell__grid acss-table-cols-dosen-skripsi acss-hover-row-group pilot-record-card pilot-record-card--dosen-skripsi">
            <div class="table-shell__cell pilot-record-card__identity" data-mobile-label="Mahasiswa">
                <strong>{{ $s->student->name ?? '-' }}</strong>
                <small>{{ $s->student->nim ?? '-' }}</small>
                <div class="acss-row-actions pilot-record-card__desktop-actions"><a class="text-link acss-action-link" href="{{ route($itemRoutePrefix.'.show', $s) }}">@include('partials.icons.eye')<span>Detail</span></a></div>
            </div>
            <div class="table-shell__cell table-shell__cell--title pilot-record-card__title">
                {{ $s->title }}
                <div><span class="pill">{{ $s->type === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi' }}</span></div>
            </div>
            <div class="table-shell__cell pilot-record-card__phase" data-mobile-label="Fase"><span class="pill">{{ str($s->current_phase)->replace(['_', '-'], ' ')->upper() }}</span></div>
            <div class="pilot-record-card__mobile-actions">
                <a class="pilot-record-card__primary-action" href="{{ route($itemRoutePrefix.'.show', $s) }}">@include('partials.icons.eye')<span>Lihat</span></a>
            </div>
        </article>
    @empty
        <div class="empty-state">Belum ada {{ str($taLabel)->lower() }} bimbingan.</div>
    @endforelse
</div>
