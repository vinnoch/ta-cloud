@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($formats) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-format-list">
            <button type="button" class="acss-sort-button" data-sort-column="name" data-sort-direction="{{ $nextDirection('name') }}">Nama Format Nilai <span>{{ $indicator('name') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="format_type" data-sort-direction="{{ $nextDirection('format_type') }}">Jenis <span>{{ $indicator('format_type') }}</span></button>
            <span>Periode</span>
            <span>Item &amp; Bobot</span>
        </div>
    @endif
    @forelse ($formats as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-format-list acss-hover-row-group">
            <div class="table-shell__cell">
                <div class="acss-stack-block">
                    <strong>{{ $item->name }}</strong>
                    <div>
                        @if (! $item->can_modify)
                            <span class="status-pill status-pill--locked">LOCKED</span>
                        @elseif ($item->is_published)
                            <span class="status-pill status-pill--published">PUBLISHED</span>
                        @else
                            <span class="status-pill status-pill--draft">DRAFT</span>
                        @endif
                    </div>
                </div>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.formats.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                    <span class="acss-action-separator">|</span>
                    <form class="inline-form" method="POST" action="{{ route('kaprodi.formats.duplicate', $item) }}">@csrf<button class="text-link acss-action-link" type="submit">@include('partials.icons.clipboard')<span>Duplikat</span></button></form>
                    @if ($item->can_modify)
                        <span class="acss-action-separator">|</span>
                        <a class="text-link acss-action-link" href="{{ route('kaprodi.formats.edit', $item) }}">@include('partials.icons.edit')<span>Edit</span></a>
                        <span class="acss-action-separator">|</span>
                        <form class="inline-form" method="POST" action="{{ route('kaprodi.formats.destroy', $item) }}" onsubmit="return confirm('Hapus format ini?')">@csrf @method('DELETE')<button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.trash')<span>Hapus</span></button></form>
                    @endif
                </div>
            </div>
            <div class="table-shell__cell"><span class="pill">{{ $item->format_type === 'sidang_proposal' ? 'Proposal' : 'Skripsi' }}</span></div>
            <div class="table-shell__cell acss-format-period-cell">@forelse ($item->periods as $period)<span class="badge badge--info">{{ $period->name }}</span>@empty - @endforelse</div>
            <div class="table-shell__cell"><strong>{{ $item->items->count() }} item</strong><small>Total {{ $item->items->sum('weight') }}%</small></div>
        </div>
    @empty
        <div class="empty-state">Belum ada data format penilaian.</div>
    @endforelse
</div>
