@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell pilot-table pilot-table--kaprodi-skripsi">
    @if (count($skripsis) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-skripsi">
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Tugas Akhir <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Fase <span>{{ $indicator('fase') }}</span></button>
        </div>
    @endif
    @forelse ($skripsis as $item)
        @php
            $displayPhase = match ($item->current_phase) {
                'proposal' => 'Proposal',
                'sidang_proposal' => 'Sidang Proposal',
                'bimbingan_skripsi' => 'Bimbingan',
                'sidang_skripsi' => 'Sidang',
                'revisi_sidang_skripsi' => 'Revisi Sidang',
                'review_dokumen_final' => 'Dokumen Final',
                'skripsi_selesai' => 'Tugas Akhir Selesai',
                default => str($item->current_phase ?: 'N/A')->replace(['_', '-'], ' ')->title()->toString(),
            };
            $itemRoutePrefix = $item->type === 'non_skripsi' ? 'kaprodi.non-skripsi' : 'kaprodi.skripsi';
        @endphp
        <article class="table-shell__row table-shell__grid acss-table-cols-skripsi-row acss-hover-row-group pilot-record-card pilot-record-card--kaprodi-skripsi">
            <div class="table-shell__cell table-shell__cell--title pilot-record-card__title">
                {{ $item->title }}
                <div><span class="pill">{{ $item->type === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi' }}</span></div>
                <div class="acss-row-actions pilot-record-card__desktop-actions">
                    <a class="text-link acss-action-link" href="{{ route($itemRoutePrefix.'.show', $item) }}">@include('partials.icons.eye')<span>Detail</span></a>
                    <span class="acss-action-separator">|</span>
                    @include('kaprodi.skripsi.partials.status-modal', [
                        'modalId' => 'skripsi-status-' . $item->id,
                        'skripsiItem' => $item,
                        'statusUpdateUrl' => route($itemRoutePrefix.'.status.update', $item),
                        'triggerLabel' => 'Edit Fase',
                        'triggerClass' => 'text-link acss-action-link',
                    ])
                </div>
            </div>
            <div class="table-shell__cell pilot-record-card__identity" data-mobile-label="Mahasiswa">
                <strong>{{ $item->student?->name ?? '-' }}</strong>
                <small>{{ $item->student?->nim ?? '-' }}</small>
            </div>
            <div class="table-shell__cell pilot-record-card__phase" data-mobile-label="Fase"><span class="pill">{{ $displayPhase }}</span></div>
            <div class="pilot-record-card__mobile-actions">
                <a class="pilot-record-card__primary-action" href="{{ route($itemRoutePrefix.'.show', $item) }}">@include('partials.icons.eye')<span>Lihat</span></a>
                <details class="pilot-record-card__overflow">
                    <summary aria-label="Tindakan lainnya">•••</summary>
                    <div class="pilot-record-card__overflow-panel">
                        <button type="button" class="text-link acss-action-link" data-status-modal-open="skripsi-status-{{ $item->id }}">@include('partials.icons.edit')<span>Edit Fase</span></button>
                    </div>
                </details>
            </div>
        </article>
    @empty
        <div class="empty-state">Belum ada data tugas akhir.</div>
    @endforelse
</div>
