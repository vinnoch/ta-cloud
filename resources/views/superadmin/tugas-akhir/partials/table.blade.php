@php
    $routePrefix = $tugasAkhirType === 'non_skripsi' ? 'superadmin.non-skripsi' : 'superadmin.skripsi';
@endphp
<div class="table-shell pilot-table pilot-table--kaprodi-skripsi">
    @if ($tugasAkhirs->isNotEmpty())
        <div class="table-shell__head table-shell__grid" style="--table-cols: minmax(12rem,2fr) minmax(11rem,1.4fr) minmax(8rem,1fr) minmax(8rem,1fr);">
            <span>Judul {{ $label }}</span>
            <span>Mahasiswa</span>
            <span>Periode</span>
            <span>Fase</span>
        </div>
    @endif

    @forelse ($tugasAkhirs as $item)
        <article class="table-shell__row table-shell__grid acss-hover-row-group" style="--table-cols: minmax(12rem,2fr) minmax(11rem,1.4fr) minmax(8rem,1fr) minmax(8rem,1fr);">
            <div class="table-shell__cell table-shell__cell--title">
                <strong>{{ $item->title }}</strong>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route($routePrefix.'.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                </div>
            </div>
            <div class="table-shell__cell"><strong>{{ $item->student?->name ?? '-' }}</strong><small>{{ $item->student?->nim ?? '-' }}</small></div>
            <div class="table-shell__cell">{{ $item->periode?->name ?? '-' }}</div>
            <div class="table-shell__cell"><span class="pill">{{ str($item->current_phase)->replace(['_', '-'], ' ')->title() }}</span></div>
        </article>
    @empty
        <div class="empty-state">Belum ada data {{ str($label)->lower() }}.</div>
    @endforelse
</div>
