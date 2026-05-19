@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($templates) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-format-list">
            <button type="button" class="acss-sort-button" data-sort-column="nama" data-sort-direction="{{ $nextDirection('nama') }}">Nama Template <span>{{ $indicator('nama') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="periode" data-sort-direction="{{ $nextDirection('periode') }}">Periode <span>{{ $indicator('periode') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="item" data-sort-direction="{{ $nextDirection('item') }}">Item <span>{{ $indicator('item') }}</span></button>
            <span>Status</span>
        </div>
    @endif
    @forelse ($templates as $item)
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
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.document-templates.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                    <span class="acss-action-separator">|</span>
                    <form class="inline-form" method="POST" action="{{ route('kaprodi.document-templates.duplicate', $item) }}">@csrf<button class="text-link acss-action-link" type="submit">@include('partials.icons.clipboard')<span>Duplikat</span></button></form>
                    @if ($item->can_modify)
                        <span class="acss-action-separator">|</span>
                        <a class="text-link acss-action-link" href="{{ route('kaprodi.document-templates.edit', $item) }}">@include('partials.icons.edit')<span>Edit</span></a>
                        <span class="acss-action-separator">|</span>
                        <form class="inline-form" method="POST" action="{{ route('kaprodi.document-templates.destroy', $item) }}" onsubmit="return confirm('Hapus template ini?')">@csrf @method('DELETE')<button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.trash')<span>Hapus</span></button></form>
                    @endif
                </div>
            </div>
            <div class="table-shell__cell acss-format-period-cell">@forelse ($item->periods as $period)<span class="badge badge--info">{{ $period->name }}</span>@empty - @endforelse</div>
            <div class="table-shell__cell"><strong>{{ $item->items->count() }} item</strong><small>{{ $item->items->where('is_required', true)->count() }} wajib</small></div>
            <div class="table-shell__cell"><span class="pill {{ $item->status === 'locked' ? 'pill--blue' : '' }}">{{ strtoupper($item->status) }}</span></div>
        </div>
    @empty
        <div class="empty-state">Belum ada data template dokumen final.</div>
    @endforelse
</div>
