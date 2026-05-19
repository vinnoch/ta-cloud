@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="notice notice--error">{{ session('error') }}</div>
    @endif

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">D</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $template->name }}</h2>
                    </div>
                    <div class="flex gap-2">
                        @if ($template->status === 'locked')
                            <span class="status-pill status-pill--locked">LOCKED</span>
                        @elseif ($template->status === 'published')
                            <span class="status-pill status-pill--published">PUBLISHED</span>
                        @else
                            <span class="status-pill status-pill--draft">DRAFT</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="acss-inline-actions form-actions form-actions--inline">
            <form class="inline-form" method="POST" action="{{ route('kaprodi.document-templates.duplicate', $template) }}">
                @csrf
                <button class="button button--muted button--inline" type="submit">Duplikat</button>
            </form>
            @if ($template->can_modify)
                <a class="button button--muted button--inline" href="{{ route('kaprodi.document-templates.edit', $template) }}">Edit Template</a>
            @endif
        </div>
    </section>

    <div class="acss-stack-sections">
        <section class="acss-crud-card">
            <div class="acss-crud-head">
                <div>
                    <h3 class="acss-card-title">Daftar Item Dokumen</h3>
                </div>
            </div>
            <div class="acss-crud-body">
                <div class="table-shell">
                    <div class="table-shell__head table-shell__grid" style="grid-template-columns: minmax(0,1.4fr) 180px;">
                        <span>Item Dokumen</span>
                        <span>Keterangan</span>
                    </div>
                    @foreach ($template->items as $item)
                        <div class="table-shell__row table-shell__grid" style="grid-template-columns: minmax(0,1.4fr) 180px;">
                            <div class="table-shell__cell">
                                <strong>{{ $item->name }}</strong>
                                <small>{{ $item->code }}</small>
                            </div>
                            <div class="table-shell__cell">
                                <span class="pill {{ $item->is_required ? 'pill--blue' : '' }}">{{ $item->is_required ? 'Wajib' : 'Opsional' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        @if (! $template->can_modify)
            <section class="card">
                <div class="section-heading">
                    <div>
                        <h3>Hubungkan Periode</h3>
                    </div>
                </div>
                <div class="acss-form-split">
                    <div class="acss-page-card">
                        <div class="acss-page-card__body">
                            <div class="section-heading"><div><h3 class="acss-card-title">Periode Tersedia</h3></div></div>
                            <div class="table-shell">
                                @forelse ($availableAddPeriodes as $period)
                                    <div class="table-shell__row table-shell__grid" style="--table-cols:minmax(0,1fr) auto;">
                                        <div class="table-shell__cell">
                                            <strong>{{ $period->name }}</strong>
                                            @if (!empty($period->kode_periode))
                                                <small>{{ $period->kode_periode }}</small>
                                            @endif
                                        </div>
                                        <div class="table-shell__cell">
                                            <form method="POST" action="{{ route('kaprodi.document-templates.add-periode', $template) }}">
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

                    <div class="acss-page-card">
                        <div class="acss-page-card__body">
                            <div class="section-heading"><div><h3 class="acss-card-title">Periode Terhubung</h3></div></div>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @forelse ($template->periodes as $period)
                                    @php $hasSubmissions = in_array($period->id, $periodeIdsWithSubmissions ?? [], true); @endphp
                                    <span class="pill pill--blue flex items-center gap-2" style="padding: 0.4rem 0.8rem; border-radius: 9999px;">
                                        <span>{{ $period->name }}@if (!empty($period->kode_periode)) ({{ $period->kode_periode }})@endif</span>
                                        @if (!$hasSubmissions)
                                            <form method="POST" action="{{ route('kaprodi.document-templates.remove-periode', [$template, $period]) }}" style="display:inline;">
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

        <section class="acss-crud-card" id="assigned-list-root" data-endpoint="{{ route('kaprodi.document-templates.show', $template) }}">
            <div class="acss-crud-head">
                <div>
                    <h3 class="acss-card-title">Skripsi Terhubung</h3>
                </div>
            </div>
            <div class="acss-crud-body">
                <form class="filter-bar" id="assigned-filter-form" method="GET" action="{{ route('kaprodi.document-templates.show', $template) }}">
                    <input type="hidden" name="assigned_sort" id="assigned-sort-input" value="{{ $assignedSort }}">
                    <input type="hidden" name="assigned_direction" id="assigned-direction-input" value="{{ $assignedDirection }}">
                    <label class="form-field acss-search-field">
                        <span>Cari Skripsi</span>
                        <input type="search" id="assigned-search-input" name="assigned_q" value="{{ $assignedSearch }}" placeholder="Cari mahasiswa atau judul...">
                    </label>
                    <label class="form-field acss-field-tight">
                        <span>Periode</span>
                        <select name="assigned_periode_id" id="assigned-periode-input">
                            <option value="">Semua Periode</option>
                            @foreach ($template->periodes as $periode)
                                <option value="{{ $periode->id }}" {{ $assignedPeriodeId === $periode->id ? 'selected' : '' }}>{{ $periode->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>

                <p id="assigned-count-text" class="acss-muted">{{ $assignedSkripsis->total() }} skripsi terhubung.</p>

                <div id="assigned-table-wrapper">@include('kaprodi.document-templates.partials.assigned-table', ['template' => $template, 'assignedSkripsis' => $assignedSkripsis, 'assignedSort' => $assignedSort, 'assignedDirection' => $assignedDirection])</div>
                <div id="assigned-pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.document-templates.partials.assigned-pagination', ['assignedSkripsis' => $assignedSkripsis])</div>
            </div>
        </section>
    </div>

    @include('partials.ajax-list-script', [
        'rootId' => 'assigned-list-root',
        'formId' => 'assigned-filter-form',
        'searchInputId' => 'assigned-search-input',
        'tableWrapperId' => 'assigned-table-wrapper',
        'paginationWrapperId' => 'assigned-pagination-wrapper',
        'countTextId' => 'assigned-count-text',
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
