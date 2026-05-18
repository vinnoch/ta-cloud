@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn(string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn(string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($dosen) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-dosen-compact">
            <button type="button" class="acss-sort-button" data-sort-column="nidn_nip"
                data-sort-direction="{{ $nextDirection('nidn_nip') }}"><span class="u-upper">NIDN / NIP</span>
                <span>{{ $indicator('nidn_nip') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="name"
                data-sort-direction="{{ $nextDirection('name') }}">Nama dosen <span>{{ $indicator('name') }}</span></button>
            <span>Email</span>
        </div>
    @endif
    @forelse ($dosen as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-compact acss-hover-row-group">
            <div class="table-shell__cell">
                <div>{{ $item->nidn_nip ?: '-' }}</div>
                <div class="acss-row-actions">
                    @php($hasRelated = ($item->has_related_records ?? false) || ($item->has_reviewed_bimbingans ?? false) || ($item->has_reviewed_grades ?? false))
                    @if (! $item->trashed())
                        <a class="text-link acss-action-link" href="{{ route('kaprodi.dosen.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                        <span class="acss-action-separator">|</span>
                        <button type="button" class="text-link acss-action-link" data-dosen-edit-open data-action="{{ route('kaprodi.dosen.update', $item) }}" data-name="{{ e($item->name) }}" data-email="{{ e($item->email) }}" data-nidn="{{ e($item->nidn_nip) }}">@include('partials.icons.edit')<span>Edit</span></button>
                        <span class="acss-action-separator">|</span>
                        @if ($hasRelated)
                            <form class="inline-form" method="POST" action="{{ route('kaprodi.dosen.archive', $item) }}" onsubmit="return confirm('Arsipkan dosen ini?')">
                                @csrf
                                <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Arsipkan</span></button>
                            </form>
                        @else
                            <form class="inline-form" method="POST" action="{{ route('kaprodi.dosen.destroy', $item->id) }}" onsubmit="return confirm('Hapus permanen dosen ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.trash')<span>Hapus</span></button>
                            </form>
                        @endif
                    @else
                        <form class="inline-form" method="POST" action="{{ route('kaprodi.dosen.restore', $item->id) }}">
                            @csrf
                            <button class="text-link acss-action-link" type="submit">@include('partials.icons.edit')<span>Pulihkan</span></button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="table-shell__cell"><strong>{{ $item->name }}</strong>@if($item->trashed())<div><span class="status-pill status-pill--locked ">ARCHIVED</span></div>@endif</div>
            <div class="table-shell__cell">{{ $item->email }}</div>
        </div>
    @empty
        <div class="empty-state">Belum ada data dosen yang cocok.</div>
    @endforelse
</div>
