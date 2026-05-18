@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($periode) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-periode">
            <button type="button" class="acss-sort-button" data-sort-column="kode" data-sort-direction="{{ $nextDirection('kode') }}">Kode periode <span>{{ $indicator('kode') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="tahun" data-sort-direction="{{ $nextDirection('tahun') }}">Tahun akademik <span>{{ $indicator('tahun') }}</span></button>
            <span>Periode akademik</span>
            <span>Status</span>
        </div>
    @endif
    @forelse ($periode as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-periode-row acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $item->kode_periode }}</strong>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.periode.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                    <span class="acss-action-separator">|</span>
                    <button type="button" class="text-link acss-action-link" data-periode-edit-open
                        data-action="{{ route('kaprodi.periode.update', $item) }}"
                        data-tahun-akademik-id="{{ $item->tahun_akademik_id }}"
                        data-semester="{{ $item->semester }}"
                        data-sk-nomor="{{ $item->sk_nomor }}"
                        data-sk-dokumen-url="{{ $item->sk_dokumen_url }}"
                        data-tgl-mulai="{{ optional($item->tgl_mulai)->format('Y-m-d') }}"
                        data-tgl-selesai="{{ optional($item->tgl_selesai)->format('Y-m-d') }}"
                        data-status="{{ $item->status }}">@include('partials.icons.edit')<span>Edit</span></button>
                </div>
            </div>
            <div class="table-shell__cell">{{ $item->tahunAkademik?->name ?? '-' }}</div>
            <div class="table-shell__cell">Semester {{ (int) $item->semester === 1 ? 'Ganjil' : 'Genap' }}</div>
            <div class="table-shell__cell">
                @php
                    $statusLabel = match ($item->status) {
                        'active' => 'AKTIF',
                        'closed' => 'CLOSED',
                        default => 'DRAFT',
                    };
                    $statusClass = match ($item->status) {
                        'active' => 'pill--green',
                        'closed' => 'pill--red',
                        default => 'pill--muted',
                    };
                @endphp
                <span class="pill {{ $statusClass }}">{{ $statusLabel }}</span>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada data periode.</div>
    @endforelse
</div>
