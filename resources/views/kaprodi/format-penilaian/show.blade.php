@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">F</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $format->name }}</h2>
                        <p>Jenis Sidang: {{ $format->format_type === 'sidang_skripsi' ? 'Skripsi' : 'Proposal' }}</p>
                    </div>
                    <div class="flex gap-2">
                        @if ($format->is_published)
                            <span class="status-pill status-pill--published">PUBLISHED</span>
                        @else
                            <span class="status-pill status-pill--draft">DRAFT</span>
                        @endif
                        @if ($isFormatLocked ?? false)
                            <span class="status-pill status-pill--locked">LOCKED</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="acss-inline-actions form-actions form-actions--inline mt-4">
            <form class="inline-form" method="POST" action="{{ route('kaprodi.formats.duplicate', $format) }}">
                @csrf
                <button class="button button--primary button--inline" type="submit">Duplikat</button>
            </form>
            @if (! ($isFormatLocked ?? false))
                <a href="{{ route('kaprodi.formats.edit', $format) }}" class="button button--primary button--inline">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M13.75 3.75a1.768 1.768 0 1 1 2.5 2.5L7.5 15H5v-2.5l8.75-8.75Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M11.25 6.25l2.5 2.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                    <span>Edit Format Nilai</span>
                </a>
            @endif
        </div>
    </section>

    <div class="acss-stack-sections">
        @if ($isFormatLocked ?? false)
            <section class="card">
                <div class="section-heading">
                    <div>
                        <h3>Hubungkan Periode</h3>
                    </div>
                </div>
                <div class="acss-form-split" style="align-items: stretch;">
                    <div class="acss-page-card" style="height: 100%;">
                        <div class="acss-page-card__body" style="height: 100%;">
                            <div class="section-heading"><div><h3 class="acss-card-title">Periode Tersedia</h3></div></div>
                            <div class="table-shell">
                                @forelse ($unattachedPeriodes as $period)
                                    <div class="table-shell__row table-shell__grid" style="--table-cols:minmax(0,1fr) auto;">
                                        <div class="table-shell__cell">
                                            <strong>{{ $period->name }}</strong>
                                            @if (!empty($period->kode_periode))
                                                <small>{{ $period->kode_periode }}</small>
                                            @endif
                                        </div>
                                        <div class="table-shell__cell">
                                            <form method="POST" action="{{ route('kaprodi.formats.add-periode', $format) }}">
                                                @csrf
                                                <input type="hidden" name="periode_id" value="{{ $period->id }}">
                                                <button class="button button--inline" type="submit">Hubungkan</button>
                                            </form>
                                        </div>
                                    </div>
                                @empty
                                    <div class="empty-state">Belum ada periode tersedia.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="acss-page-card" style="height: 100%;">
                        <div class="acss-page-card__body" style="height: 100%;">
                            <div class="section-heading"><div><h3 class="acss-card-title">Periode Terhubung</h3></div></div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @forelse ($format->periodes as $period)
                                    @php $hasGrades = in_array($period->id, $periodeIdsWithGrades ?? [], true); @endphp
                                    <span class="pill pill--blue flex items-center gap-2" style="padding: 0.4rem 0.8rem; border-radius: 9999px;">
                                        <span>{{ $period->name }}@if (!empty($period->kode_periode)) ({{ $period->kode_periode }})@endif</span>
                                        @if (!$hasGrades)
                                            <form method="POST" action="{{ route('kaprodi.formats.remove-periode', [$format, $period]) }}" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-bold focus:outline-none" style="background:none; border:none; padding:0; font-size:1.1rem; line-height:1; cursor:pointer;" title="Lepas periode">×</button>
                                            </form>
                                        @endif
                                    </span>
                                @empty
                                    <div class="empty-state w-full text-center py-4">Belum ada periode terhubung.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
        <section class="acss-crud-card" id="assigned-list-root" data-endpoint="{{ route('kaprodi.formats.show', $format) }}">
            <div class="acss-crud-head">
                <div>
                    <h3 class="acss-card-title">Nilai Sidang Terhubung</h3>
                </div>
            </div>

            <div class="acss-crud-body">
                <form class="filter-bar" id="assigned-filter-form" method="GET" action="{{ route('kaprodi.formats.show', $format) }}" autocomplete="off">
                    <input type="hidden" id="assigned-sort-input" name="assigned_sort" value="{{ $assignedSort ?? '' }}">
                    <input type="hidden" id="assigned-direction-input" name="assigned_direction" value="{{ $assignedDirection ?? 'desc' }}">
                    
                    <label class="form-field acss-search-field">
                        <span>Cari Mahasiswa</span>
                        <input type="search" id="assigned-search-input" name="assigned_q" value="{{ $assignedSearch ?? '' }}" placeholder="Cari nama atau NIM">
                    </label>

                    <label class="form-field acss-field-tight">
                        <span>Periode</span>
                        <select name="assigned_periode_id" id="assigned-periode-input">
                            <option value="">Semua Periode</option>
                            @foreach ($format->periodes as $period)
                                <option value="{{ $period->id }}" {{ (string) ($assignedPeriodeId ?? '') === (string) $period->id ? 'selected' : '' }}>{{ $period->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>

                <p id="assigned-count-text" class="acss-muted ">{{ $assignedSkripsis->total() }} skripsi terhubung.</p>
                
                <div id="assigned-table-wrapper">
                    @include('kaprodi.format-penilaian.partials.assigned-table', [
                        'format' => $format, 
                        'assignedSkripsis' => $assignedSkripsis, 
                        'assignedSort' => $assignedSort ?? '', 
                        'assignedDirection' => $assignedDirection ?? 'desc'
                    ])
                </div>

                <div id="assigned-pagination-wrapper" class="acss-pagination-spacer">
                    @include('kaprodi.format-penilaian.partials.assigned-pagination', ['assignedSkripsis' => $assignedSkripsis])
                </div>
            </div>
        </section>
    </div>

@include('partials.ajax-list-script', [
    'rootId' => 'assigned-list-root', 
    'formId' => 'assigned-filter-form', 
    'searchInputId' => 'assigned-search-input', 
    'tableWrapperId' => 'assigned-table-wrapper', 
    'paginationWrapperId' => 'assigned-pagination-wrapper', 
    'countTextId' => 'assigned-count-text'
])

<script>
(() => {
    const form = document.getElementById('assigned-filter-form');
    const sortInput = document.getElementById('assigned-sort-input');
    const directionInput = document.getElementById('assigned-direction-input');
    const tableWrapper = document.getElementById('assigned-table-wrapper');

    if (!form || !sortInput || !directionInput || !tableWrapper) return;

    tableWrapper.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sort-column]');
        if (!button) return;

        sortInput.value = button.dataset.sortColumn || '';
        directionInput.value = button.dataset.sortDirection || 'desc';
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });
})();
</script>
@endsection
