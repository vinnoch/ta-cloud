@php
    $sort = $sort ?? 'tanggal';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($proposals) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-proposal-subs">
            <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="periode" data-sort-direction="{{ $nextDirection('periode') }}">Periode <span>{{ $indicator('periode') }}</span></button>
        </div>
    @endif
    @forelse ($proposals as $skripsi)
        <div class="table-shell__row table-shell__grid acss-table-cols-proposal-subs acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $skripsi->documentVersions->first()?->created_at?->format('d/m/Y') ?? '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $skripsi->documentVersions->first()?->created_at?->format('H:i') ?? '' }}</div>
                @if ($skripsi->proposal_review_status === 'revision_required')
                    <div class=""><span class="pill">Butuh Revisi</span></div>
                @elseif ($skripsi->proposal_review_status === 'approved')
                    <div class=""><span class="pill">Disetujui</span></div>
                @endif
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.proposal', $skripsi) }}">@include('partials.icons.eye')<span>Proposal</span></a>
                </div>
            </div>
            <div class="table-shell__cell">
                <strong>{{ $skripsi->student?->name ?? '-' }}</strong>
                <small>{{ $skripsi->student?->nim ?? '-' }}</small>
            </div>
            <div class="table-shell__cell table-shell__cell--title">{{ $skripsi->title ?: '-' }}</div>
            <div class="table-shell__cell">{{ $skripsi->periode?->name ?? '-' }}</div>
        </div>
    @empty
        <div class="empty-state">Belum ada pengajuan proposal.</div>
    @endforelse
</div>
