@php
    $sort = $sort ?? 'tanggal';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($requests) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-sidang-reqs">
            <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Fase <span>{{ $indicator('fase') }}</span></button>
            <span>Status</span>
        </div>
    @endif
    @forelse ($requests as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-sidang-reqs acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $item->submitted_at?->format('d/m/Y') ?? '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $item->submitted_at?->format('H:i') ?? '' }}</div>
                @if ($item->status !== 'approved')
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $item->skripsi) }}">@include('partials.icons.eye')<span>Permohonan Sidang</span></a>
                    @if ($item->status === 'submitted')
                        <form method="POST" action="{{ route('kaprodi.skripsi.sidang-request.approve', [$item->skripsi, $item]) }}">
                            @csrf
                            <button class="button button--small button--success" type="submit">Setujui</button>
                        </form>
                    @endif
                </div>
                @endif
            </div>
            <div class="table-shell__cell"><strong>{{ $item->skripsi?->student?->name ?? '-' }}</strong><small>{{ $item->skripsi?->student?->nim ?? '-' }}</small></div>
            <div class="table-shell__cell table-shell__cell--title">{{ $item->skripsi?->title ?: '-' }}</div>
            <div class="table-shell__cell">
                <span class="pill">{{ $item->role_type === 'mahasiswa' ? 'Sidang Proposal' : 'Sidang Skripsi' }}</span>
                @if($item->role_type !== 'mahasiswa')
                    <div class="mt-1"><small>Dosen: {{ $item->lecturer?->name ?? '-' }}</small></div>
                @endif
            </div>
            <div class="table-shell__cell">
                <span class="pill {{ $item->status === 'approved' ? 'pill--green' : ($item->status === 'rejected' ? 'pill--red' : 'pill--yellow') }}">
                    {{ $item->status === 'approved' ? 'Disetujui' : ($item->status === 'rejected' ? 'Ditolak' : 'Pending') }}
                </span>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada permohonan sidang.</div>
    @endforelse
</div>
