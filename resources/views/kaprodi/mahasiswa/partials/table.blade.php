@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($mahasiswa) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-mahasiswa">
            <button type="button" class="acss-sort-button" data-sort-column="nim" data-sort-direction="{{ $nextDirection('nim') }}">NIM <span>{{ $indicator('nim') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="name" data-sort-direction="{{ $nextDirection('name') }}">Nama mahasiswa <span>{{ $indicator('name') }}</span></button>
            <span>Email</span>
        </div>
    @endif
    @forelse ($mahasiswa as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-mahasiswa acss-hover-row-group">
            <div class="table-shell__cell">
                <div>{{ $item->nim ?? '-' }}</div>
                <div class="acss-row-actions">
                    @if (! $item->trashed())
                        <a class="text-link acss-action-link" href="{{ route('kaprodi.mahasiswa.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                        <span class="acss-action-separator">|</span>
                        <button type="button" class="text-link acss-action-link" data-mahasiswa-edit-open data-action="{{ route('kaprodi.mahasiswa.update', $item) }}" data-name="{{ e($item->name) }}" data-nim="{{ e($item->nim) }}" data-email="{{ e($item->email) }}">@include('partials.icons.edit')<span>Edit</span></button>
                        <span class="acss-action-separator">|</span>
                        @if ($item->has_running_skripsi)
                            <form class="inline-form" method="POST" action="{{ route('kaprodi.mahasiswa.archive', $item) }}" onsubmit="return confirm('Arsipkan mahasiswa ini?')">
                                @csrf
                                <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Arsipkan</span></button>
                            </form>
                        @else
                            <form class="inline-form" method="POST" action="{{ route('kaprodi.mahasiswa.destroy', $item) }}" onsubmit="return confirm('Hapus mahasiswa ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.trash')<span>Hapus</span></button>
                            </form>
                        @endif
                    @else
                        <form class="inline-form" method="POST" action="{{ route('kaprodi.mahasiswa.restore', $item->id) }}" onsubmit="return confirm('Pulihkan mahasiswa ini?')">
                            @csrf
                            <button class="text-link acss-action-link" type="submit">@include('partials.icons.archive')<span>Pulihkan</span></button>
                        </form>
                    @endif
                </div>
            </div>
            <div class="table-shell__cell">
                <strong>{{ $item->name ?? '-' }}</strong>
                @if ($item->trashed())
                    <div><span class="status-pill status-pill--locked mt-2">ARCHIVED</span></div>
                @endif
            </div>
            <div class="table-shell__cell">{{ $item->email }}</div>
        </div>
    @empty
        <div class="empty-state">Belum ada mahasiswa terdaftar.</div>
    @endforelse
</div>
