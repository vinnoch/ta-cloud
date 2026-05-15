@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ strtoupper(substr((string) $periode->kode_periode, 0, 2)) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $periode->kode_periode }}</h2>
                        <p>{{ $periode->tahunAkademik?->name ?? ($periode->name ?? '-') }} • Semester {{ $periode->semester }}</p>
                    </div>
                    <span class="status-pill">{{ ucfirst($periode->status) }}</span>
                </div>
            </div>
        </div>

    </section>


    <section class="card">
        <div class="section-heading">
            <div>
                <h3>Informasi Periode</h3>
            </div>
        </div>
        <div class="history-table">
            <div class="history-table__row">
                <div>
                    <strong>Rentang Validitas</strong>
                    <small>{{ $periode->tgl_mulai?->format('d/m/Y') }} - {{ $periode->tgl_selesai?->format('d/m/Y') }}</small>
                </div>
            </div>
            <div class="history-table__row">
                <div>
                    <strong>SK Number</strong>
                    <small>{{ $periode->sk_nomor ?: '-' }}</small>
                </div>
            </div>
            <div class="history-table__row">
                <div>
                    <strong>Dokumen SK</strong>
                    <small>
                        @if ($periode->sk_dokumen_url)
                            <a href="{{ $periode->sk_dokumen_url }}" target="_blank">Lihat dokumen</a>
                        @else
                            -
                        @endif
                    </small>
                </div>
            </div>
        </div>

        <div class="form-actions form-actions--inline mt-4">
            <button type="button" class="button button--muted button--inline" data-periode-show-edit-open>Edit Periode</button>
            @if ($hasLinkedData)
                <form method="POST" action="{{ route('kaprodi.periode.archive', $periode) }}" onsubmit="return confirm('Arsipkan periode ini?')">
                    @csrf
                    <button class="button button--danger button--inline" type="submit">Arsipkan Periode</button>
                </form>
            @else
                <form method="POST" action="{{ route('kaprodi.periode.destroy', $periode) }}" onsubmit="return confirm('Hapus periode ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="button button--danger button--inline" type="submit">Hapus Periode</button>
                </form>
            @endif
            
        </div>
    </section>

    <section class="acss-crud-card" id="assigned-list-root" data-endpoint="{{ route('kaprodi.periode.show', $periode) }}">
        <div class="acss-crud-head">
            <div>
                <h3 class="acss-card-title">Skripsi Aktif</h3>
                <p class="acss-muted mt-1" id="assigned-count-text">{{ $assignedSkripsis->total() }} skripsi aktif terhubung.</p>
            </div>
        </div>
        <div class="acss-crud-body">
            <form id="assigned-filter-form" class="filter-bar" role="search">
                <input type="hidden" id="assigned-sort-input" name="sort" value="{{ $assignedSort ?? '' }}">
                <input type="hidden" id="assigned-direction-input" name="direction" value="{{ $assignedDirection ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Mahasiswa/Judul Skripsi</span>
                    <input id="assigned-search-input" type="search" name="q" value="{{ $assignedSearch ?? '' }}" placeholder="Cari nama, NIM, atau judul skripsi">
                </label>
                <label class="form-field"><span>Fase</span><select name="fase" id="assigned-fase-filter"><option value="">Semua Fase</option><option value="proposal" {{ ($assignedFase ?? '') === 'proposal' ? 'selected' : '' }}>Sidang Proposal</option><option value="bimbingan" {{ ($assignedFase ?? '') === 'bimbingan' ? 'selected' : '' }}>Bimbingan Skripsi</option><option value="sidang_skripsi" {{ ($assignedFase ?? '') === 'sidang_skripsi' ? 'selected' : '' }}>Sidang Skripsi</option><option value="review_dokumen_final" {{ ($assignedFase ?? '') === 'review_dokumen_final' ? 'selected' : '' }}>Review Dokumen Final</option></select></label>
            </form>
            <div id="assigned-table-wrapper">
                @include('kaprodi.periode.partials.assigned-table', ['periode' => $periode, 'assignedSkripsis' => $assignedSkripsis, 'assignedSort' => $assignedSort ?? '', 'assignedDirection' => $assignedDirection ?? 'desc', 'assignedFase' => $assignedFase ?? ''])
            </div>
            <div id="assigned-pagination-wrapper">
                @include('kaprodi.periode.partials.assigned-pagination', ['assignedSkripsis' => $assignedSkripsis])
            </div>
        </div>
    </section>
@include('partials.ajax-list-script', ['rootId' => 'assigned-list-root', 'formId' => 'assigned-filter-form', 'searchInputId' => 'assigned-search-input', 'tableWrapperId' => 'assigned-table-wrapper', 'paginationWrapperId' => 'assigned-pagination-wrapper', 'countTextId' => 'assigned-count-text'])
<script>
(() => {
    const form = document.getElementById('assigned-filter-form');
    const sortInput = document.getElementById('assigned-sort-input');
    const directionInput = document.getElementById('assigned-direction-input');
    const tableWrapper = document.getElementById('assigned-table-wrapper');
    const faseFilter = document.getElementById('assigned-fase-filter');

    if (!form || !sortInput || !directionInput || !tableWrapper) return;

    if (faseFilter) {
        faseFilter.addEventListener('change', () => {
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
        });
    }

    tableWrapper.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sort-column]');
        if (!button) return;

        sortInput.value = button.dataset.sortColumn || '';
        directionInput.value = button.dataset.sortDirection || 'desc';
        form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    });
})();
</script>


    <div class="acss-modal" data-periode-show-edit-modal hidden>
        <div class="acss-modal__backdrop" data-periode-show-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--large acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Periode</h3>
                </div>
                <button type="button" class="acss-modal__close" data-periode-show-edit-close aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.periode.update', $periode) }}">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                <div class="acss-form-stack-tight">
                    <section class="acss-crud-card">
                        <div class="acss-crud-head">
                            <div>
                                <h3 class="acss-card-title">Tahun Akademik</h3>
                                <p class="acss-muted mt-1">Pilih tahun akademik dan semester periode.</p>
                            </div>
                        </div>
                        <div class="acss-crud-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <label class="form-field acss-field-tight">
                                    <span>Tahun Akademik</span>
                                    <select name="tahun_akademik_id" required>
                                        @foreach ($periode->tahunAkademik->newQuery()->orderByDesc('tahun_awal')->get() as $year)
                                            <option value="{{ $year->id }}" {{ old('tahun_akademik_id', $periode->tahun_akademik_id) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Semester</span>
                                    <select name="semester" required>
                                        <option value="1" {{ old('semester', $periode->semester) == '1' ? 'selected' : '' }}>1 (Ganjil)</option>
                                        <option value="2" {{ old('semester', $periode->semester) == '2' ? 'selected' : '' }}>2 (Genap)</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </section>
                    <section class="acss-crud-card">
                        <div class="acss-crud-head">
                            <div>
                                <h3 class="acss-card-title">Dokumen SK</h3>
                                <p class="acss-muted mt-1">Lengkapi dokumen SK dan masa berlaku periode.</p>
                            </div>
                        </div>
                        <div class="acss-crud-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <label class="form-field acss-field-tight">
                                    <span>Nomor SK</span>
                                    <input type="text" name="sk_nomor" value="{{ old('sk_nomor', $periode->sk_nomor) }}" placeholder="Contoh: SK-FTI/2026/014">
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Link Dokumen SK</span>
                                    <input type="url" name="sk_dokumen_url" value="{{ old('sk_dokumen_url', $periode->sk_dokumen_url) }}" placeholder="https://drive.google.com/file/d/.../view">
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Valid Sejak</span>
                                    <input type="date" name="tgl_mulai" value="{{ old('tgl_mulai', $periode->tgl_mulai?->format('Y-m-d')) }}" required>
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Valid Sampai</span>
                                    <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai', $periode->tgl_selesai?->format('Y-m-d')) }}" required>
                                </label>
                            </div>
                        </div>
                    </section>
                    <div class="grid grid-cols-1 gap-5 md:w-1/3">
                        <label class="form-field acss-field-tight">
                            <span>Status Periode</span>
                            <select name="status" required>
                                <option value="draft" {{ old('status', $periode->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="active" {{ old('status', $periode->status) === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="closed" {{ old('status', $periode->status) === 'closed' ? 'selected' : '' }}>Closed</option>
                            </select>
                        </label>
                    </div>
                </div>
                </div>
                <div class="form-actions form-actions--inline mt-4">
                    <button type="button" class="button button--muted button--inline" data-periode-show-edit-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

<script>
(() => {
    const modal = document.querySelector('[data-periode-show-edit-modal]');
    const toggleModal = (show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-periode-show-edit-open]')) toggleModal(true);
        if (event.target.closest('[data-periode-show-edit-close]')) toggleModal(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(false);
    });
})();
</script>
@endsection
