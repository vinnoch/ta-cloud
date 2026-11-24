@php
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell pilot-table">
    @if ($proposals->isNotEmpty())
        <div class="table-shell__head table-shell__grid acss-table-cols-proposal-subs">
            <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Tugas Akhir <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="periode" data-sort-direction="{{ $nextDirection('periode') }}">Periode <span>{{ $indicator('periode') }}</span></button>
            <span>Status</span>
        </div>
    @endif

    @forelse ($proposals as $skripsi)
        @php($proposalHref = app(\App\Services\TugasAkhirRouteService::class)->route('dosen', $skripsi, 'show', $skripsi))
        <div class="table-shell__row table-shell__grid acss-table-cols-proposal-subs acss-hover-row-group pilot-record-card pilot-record-card--dosen-proposal">
            <div class="table-shell__cell pilot-record-card__date" data-mobile-label="Tanggal">
                <strong>{{ $skripsi->documentVersions->first()?->created_at?->format('d/m/Y') ?? '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $skripsi->documentVersions->first()?->created_at?->format('H:i') ?? '' }}</div>
                <div class="acss-row-actions pilot-record-card__desktop-actions">
                    <a class="text-link acss-action-link" href="{{ $proposalHref }}">@include('partials.icons.eye')<span>Proposal</span></a>
                </div>
            </div>
            <div class="table-shell__cell pilot-record-card__identity">
                <strong>{{ $skripsi->student?->name ?? '-' }}</strong>
                <small>{{ $skripsi->student?->nim ?? '-' }}</small>
            </div>
            <div class="table-shell__cell table-shell__cell--title pilot-record-card__title">
                <div>{{ $skripsi->title ?: '-' }}</div>
                <div><span class="pill">{{ $skripsi->type === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi' }}</span></div>
            </div>
            <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Periode">{{ $skripsi->periode?->name ?? '-' }}</div>
            <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Status">
                @if ($skripsi->proposal_review_status === 'approved')
                    <span class="pill pill--green">Disetujui</span>
                @elseif (in_array($skripsi->proposal_review_status, ['rejected', 'revision_required'], true))
                    <span class="pill pill--red">Revisi / Tolak</span>
                @else
                    <span class="pill">Pending</span>
                @endif
            </div>
            <div class="pilot-record-card__mobile-actions">
                <a class="pilot-record-card__primary-action" href="{{ $proposalHref }}">@include('partials.icons.eye')<span>Buka Proposal</span></a>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada proposal mahasiswa bimbingan.</div>
    @endforelse
</div>
