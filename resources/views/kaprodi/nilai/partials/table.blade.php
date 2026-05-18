@php
    $sort = $sort ?? '';
    $direction = $direction ?? 'desc';
    $nextDirection = fn(string $column) => $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
    $indicator = fn(string $column) => $sort !== $column ? '↕' : ($direction === 'asc' ? '↑' : '↓');
@endphp
<div class="table-shell">
    @if (count($data_nilai) > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-nilai-list">
            <button type="button" class="acss-sort-button" data-sort-column="mahasiswa" data-sort-direction="{{ $nextDirection('mahasiswa') }}">Mahasiswa <span>{{ $indicator('mahasiswa') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="judul" data-sort-direction="{{ $nextDirection('judul') }}">Judul Skripsi <span>{{ $indicator('judul') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="fase" data-sort-direction="{{ $nextDirection('fase') }}">Sidang <span>{{ $indicator('fase') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="dosen" data-sort-direction="{{ $nextDirection('dosen') }}">Dosen <span>{{ $indicator('dosen') }}</span></button>
            <button type="button" class="acss-sort-button" data-sort-column="nilai" data-sort-direction="{{ $nextDirection('nilai') }}">Nilai <span>{{ $indicator('nilai') }}</span></button>
        </div>
    @endif
    @forelse ($data_nilai as $item)
        <div class="table-shell__row table-shell__grid acss-table-cols-nilai-list acss-hover-row-group">
            <div class="table-shell__cell">
                <strong>{{ $item->student_name }}</strong>
                <small>{{ $item->nim }}</small>
                <div class="acss-row-actions">
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $item->skripsi_id) }}">
                        @include('partials.icons.eye')<span>Skripsi</span>
                    </a>
                    <span class="acss-action-separator">|</span>
                    <a class="text-link acss-action-link" href="{{ route('kaprodi.formats.grades.show', [$item->format_penilaian_id ?? 0, $item->skripsi_id]) }}">
                        @include('partials.icons.eye')<span>Nilai</span>
                    </a>
                </div>
            </div>
            <div class="table-shell__cell">{{ $item->title }}</div>
            <div class="table-shell__cell">
                <span class="pill">{{ $item->grade_event === 'sidang_skripsi' ? 'Sidang Skripsi' : 'Sidang Proposal' }}</span>
            </div>
            <div class="table-shell__cell">
                <strong>{{ $item->reviewer_name ?? '-' }}</strong>
                <small>{{ str((string) $item->role_type)->replace('_', ' ')->title() }}</small>
            </div>
            <div class="table-shell__cell">
                <span class="pill pill--score-circle pill--score-circle-neutral">{{ rtrim(rtrim(number_format((float) $item->score, 2, '.', ''), '0'), '.') }}</span>
                <div class=" text-xs acss-muted">
                    {{ \Carbon\Carbon::parse($item->last_added_at)->format('d/m/Y') }}<br>
                    {{ \Carbon\Carbon::parse($item->last_added_at)->format('H:i') }}
                </div>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada nilai.</div>
    @endforelse
</div>
