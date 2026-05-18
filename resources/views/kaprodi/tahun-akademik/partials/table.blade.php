@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($tahunAkademik) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-ta">
            <button type="button" class="acss-sort-button" data-sort-column="name" data-sort-direction="{{ $nextDirection('name') }}">Tahun akademik <span>{{ $indicator('name') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="rentang" data-sort-direction="{{ $nextDirection('rentang') }}">Rentang tahun <span>{{ $indicator('rentang') }}</span></button>
            <span>Status</span>
        </div>
    @endif
    @forelse ($tahunAkademik as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-ta-row acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $item->name }}</strong>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.tahun-akademik.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                    <span class="acss-action-separator">|</span>
                    <button type="button" class="text-link acss-action-link" data-ta-edit-open
                        data-action="{{ route('kaprodi.tahun-akademik.update', $item) }}"
                        data-tahun-awal="{{ $item->tahun_awal }}"
                        data-tahun-akhir="{{ $item->tahun_akhir }}">@include('partials.icons.edit')<span>Edit</span></button>
                    <span class="acss-action-separator">|</span>
                    @if ($item->has_periods ?? false)
                        <form class="inline-form" method="POST" action="{{ route('kaprodi.tahun-akademik.archive', $item) }}" onsubmit="return confirm('Arsipkan tahun akademik ini?')">
                            @csrf
                            <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Arsipkan</span></button>
                        </form>
                    @else
                        <form class="inline-form" method="POST" action="{{ route('kaprodi.tahun-akademik.destroy', $item) }}" onsubmit="return confirm('Hapus tahun akademik ini?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.trash')<span>Hapus</span></button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="table-shell__cell">{{ $item->tahun_awal }} - {{ $item->tahun_akhir }}</div>
            <div class="table-shell__cell">
                <span class="pill {{ $item->trashed() ? 'pill--red' : 'pill--green' }}">
                    {{ $item->trashed() ? 'ARSIP' : 'AKTIF' }}
                </span>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada data tahun akademik.</div>
    @endforelse
</div>
