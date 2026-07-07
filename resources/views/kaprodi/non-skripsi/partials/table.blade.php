@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($nonSkripsis) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-non-skripsi">
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="link" data-sort-direction="{{ $nextDirection('link') }}">Link Artikel Jurnal <span>{{ $indicator('link') }}</span></button>
        </div>
    @endif
    @forelse ($nonSkripsis as $item)
        @php
            $articleUrl = $item->journal_article_url ?: $item->nonSkripsiRecord?->publication_url;
        @endphp
        <div class="table-shell__row table-shell__grid acss-table-cols-non-skripsi-row acss-hover-row-group">
            <div class="table-shell__cell table-shell__cell--title">
                {{ $item->title }}
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.non-skripsi.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                </div>
            </div>
            <div class="table-shell__cell">
                <strong>{{ $item->student?->name ?? '-' }}</strong>
                <small>{{ $item->student?->nim ?? '-' }}</small>
            </div>
            <div class="table-shell__cell">
                @if ($articleUrl)
                    <a class="text-link" href="{{ $articleUrl }}" target="_blank" rel="noopener noreferrer">{{ $articleUrl }}</a>
                @else
                    <span class="acss-muted">-</span>
                @endif
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada data non-skripsi.</div>
    @endforelse
</div>
