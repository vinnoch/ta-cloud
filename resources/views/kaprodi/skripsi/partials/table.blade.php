@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell mt-4">
    @if (count($skripsis) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-skripsi">
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Fase <span>{{ $indicator('fase') }}</span></button>
        </div>
    @endif
    @forelse ($skripsis as $item)
        @php $displayPhase = str($item->current_phase ?: 'N/A')->replace(['_', '-'], ' ')->title()->toString(); @endphp
        <div class="table-shell__row table-shell__grid acss-table-cols-skripsi-row acss-hover-row-group">
            <div class="table-shell__cell table-shell__cell--title">
                {{ $item->title }}
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $item) }}">@include('partials.icons.eye')<span>Skripsi</span></a>
                    <span class="acss-action-separator">|</span>
                    @include('kaprodi.skripsi.partials.status-modal', [
                        'modalId' => 'skripsi-status-' . $item->id,
                        'skripsiItem' => $item,
                        'statusUpdateUrl' => route('kaprodi.skripsi.status.update', $item),
                        'triggerLabel' => 'Edit Fase',
                        'triggerClass' => 'text-link acss-action-link',
                    ])
                </div>
            </div>
            <div class="table-shell__cell">
                <strong>{{ $item->student?->name ?? '-' }}</strong>
                <small>{{ $item->student?->nim ?? '-' }}</small>
            </div>
            <div class="table-shell__cell"><span class="pill">{{ $displayPhase }}</span></div>
        </div>
    @empty
        <div class="empty-state">Belum ada data monitoring skripsi.</div>
    @endforelse
</div>
