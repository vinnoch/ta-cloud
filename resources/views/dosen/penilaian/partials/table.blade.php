@php
    $sort = $sort ?? 'tanggal';
    $direction = $direction ?? 'desc';
    $nextDirection = fn (string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn (string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    <div class="table-shell__head table-shell__grid acss-table-cols-dosen-grade">
        <button type="button" class="acss-sort-button" data-sort-column="tanggal" data-sort-direction="{{ $nextDirection('tanggal') }}">Tanggal <span>{{ $indicator('tanggal') }}</span></button>
        <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
        <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
        <span>Nilai Sidang</span>
        <button type="button" class="acss-sort-button" data-sort-column="peran" data-sort-direction="{{ $nextDirection('peran') }}">Peran <span>{{ $indicator('peran') }}</span></button>
    </div>
    @forelse($gradingQueue as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-grade acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $item['date'] ? $item['date']->format('d/m/Y') : '-' }}</strong>
                <div class="text-[10px] acss-muted">{{ $item['date'] ? $item['date']->format('H:i') : '' }}</div>
                <div class="acss-row-actions"><a class="text-link acss-action-link" href="{{ $item['href'] }}">@include('partials.icons.clipboard')<span>Isi Nilai</span></a><a class="text-link acss-action-link" href="{{ $item['skripsi_href'] }}">@include('partials.icons.eye')<span>Skripsi</span></a></div>
            </div>
            <div class="table-shell__cell"><strong>{{ $item['student'] }}</strong></div>
            <div class="table-shell__cell table-shell__cell--title">{{ $item['title'] }}</div>
            <div class="table-shell__cell"><span class="pill">{{ $item['fase'] }}</span></div>
            <div class="table-shell__cell"><span class="pill pill--blue">{{ $item['role'] }}</span></div>
        </div>
    @empty
        <div class="empty-state">Belum ada antrian penilaian.</div>
    @endforelse
</div>
