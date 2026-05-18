@extends('layouts.app')
@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.periode.index') }}">
        <div class="acss-crud-head acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <h1 class="acss-page-title">Daftar Periode</h1>
                <p class="acss-muted " id="count-text">{{ $periode->total() }} periode akademik tersedia.</p>
            </div>
            <div class="acss-crud-head__actions">
                <button type="button" class="button button--inline" data-periode-create-open>@include('partials.icons.plus')<span>Tambah Periode</span></button>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="filter-form" method="GET" action="{{ route('kaprodi.periode.index') }}">
                    <input type="hidden" id="sort-input" name="sort" value="{{ $sort ?? '' }}">
                    <input type="hidden" id="direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Periode</span>
                    <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari kode atau nama">
                </label>
                
            </form>

            <div id="table-wrapper">@include('kaprodi.periode.partials.table', ['periode' => $periode, 'sort' => $sort ?? '', 'direction' => $direction ?? 'desc'])</div>
            <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.periode.partials.pagination', ['periode' => $periode])</div>
        </div>
    </section>

@include('partials.ajax-list-script', [
    'rootId' => 'list-root',
    'formId' => 'filter-form',
    'searchInputId' => 'search-input',
    'tableWrapperId' => 'table-wrapper',
    'paginationWrapperId' => 'pagination-wrapper',
    'countTextId' => 'count-text',
])

<script>
document.addEventListener('DOMContentLoaded', () => {
    (() => {
    const form = document.getElementById('filter-form');
    const sortInput = document.getElementById('sort-input');
    const directionInput = document.getElementById('direction-input');
    const tableWrapper = document.getElementById('table-wrapper');
    const editModal = document.querySelector('[data-periode-edit-modal]');
    const editForm = document.getElementById('periode-edit-form');
    const toggleModal = (modal, show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };
    if (!form || !sortInput || !directionInput || !tableWrapper) return;
    tableWrapper.addEventListener('click', (event) => {
        const button = event.target.closest('[data-sort-column]');
        if (button) {
            sortInput.value = button.dataset.sortColumn || '';
            directionInput.value = button.dataset.sortDirection || 'desc';
            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            return;
        }
        const editButton = event.target.closest('[data-periode-edit-open]');
        if (editButton && editForm) {
            editForm.action = editButton.dataset.action || '';
            editForm.querySelector('[data-periode-edit-tahun-akademik-id]').value = editButton.dataset.tahunAkademikId || '';
            editForm.querySelector('[data-periode-edit-semester]').value = editButton.dataset.semester || '';
            editForm.querySelector('[data-periode-edit-sk-nomor]').value = editButton.dataset.skNomor || '';
            editForm.querySelector('[data-periode-edit-sk-dokumen-url]').value = editButton.dataset.skDokumenUrl || '';
            editForm.querySelector('[data-periode-edit-tgl-mulai]').value = editButton.dataset.tglMulai || '';
            editForm.querySelector('[data-periode-edit-tgl-selesai]').value = editButton.dataset.tglSelesai || '';
            const statusField = editForm.querySelector('[data-periode-edit-status]');
            if (statusField) statusField.value = editButton.dataset.status || 'draft';
            toggleModal(editModal, true);
        }
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-periode-edit-close]')) {
            toggleModal(event.target.closest('.acss-modal') || editModal, false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(editModal, false);
    });
    })();
});
</script>


    <div class="acss-modal" data-periode-edit-modal hidden>
        <div class="acss-modal__backdrop" data-periode-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--large acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Periode</h3>
                </div>
                <button type="button" class="acss-modal__close" data-periode-edit-close aria-label="Tutup">×</button>
            </div>
            <form id="periode-edit-form" class="acss-form-stack-tight" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                <div class="acss-form-stack-tight">
                    <section class="acss-crud-card">
                        <div class="acss-crud-head">
                            <div>
                                <h3 class="acss-card-title">Tahun Akademik</h3>
                                <p class="acss-muted ">Pilih tahun akademik dan semester periode.</p>
                            </div>
                        </div>
                        <div class="acss-crud-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <label class="form-field acss-field-tight">
                                    <span>Tahun Akademik</span>
                                    <select name="tahun_akademik_id" data-periode-edit-tahun-akademik-id required>
                                        @foreach ($tahunAkademiks as $year)
                                            <option value="{{ $year->id }}">{{ $year->name }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Semester</span>
                                    <select name="semester" data-periode-edit-semester required>
                                        <option value="1">1 (Ganjil)</option>
                                        <option value="2">2 (Genap)</option>
                                    </select>
                                </label>
                            </div>
                        </div>
                    </section>
                    <section class="acss-crud-card">
                        <div class="acss-crud-head">
                            <div>
                                <h3 class="acss-card-title">Dokumen SK</h3>
                                <p class="acss-muted ">Lengkapi dokumen SK dan masa berlaku periode.</p>
                            </div>
                        </div>
                        <div class="acss-crud-body">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <label class="form-field acss-field-tight">
                                    <span>Nomor SK</span>
                                    <input type="text" name="sk_nomor" data-periode-edit-sk-nomor placeholder="Contoh: SK-FTI/2026/014">
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Link Dokumen SK</span>
                                    <input type="url" name="sk_dokumen_url" data-periode-edit-sk-dokumen-url placeholder="https://drive.google.com/file/d/.../view">
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Valid Sejak</span>
                                    <input type="date" name="tgl_mulai" data-periode-edit-tgl-mulai required>
                                </label>
                                <label class="form-field acss-field-tight">
                                    <span>Valid Sampai</span>
                                    <input type="date" name="tgl_selesai" data-periode-edit-tgl-selesai required>
                                </label>
                            </div>
                        </div>
                    </section>
                    <div class="grid grid-cols-1 gap-5 md:w-1/3">
                        <label class="form-field acss-field-tight">
                            <span>Status Periode</span>
                            <select name="status" data-periode-edit-status required>
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="closed">Closed</option>
                            </select>
                        </label>
                    </div>
                </div>
                </div>
                <div class="form-actions form-actions--inline ">
                    <button type="button" class="button button--muted button--inline" data-periode-edit-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acss-modal" data-periode-create-modal hidden>
        <div class="acss-modal__backdrop" data-periode-create-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--large acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambah Periode</h3>
                </div>
                <button type="button" class="acss-modal__close" data-periode-create-close aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.periode.store') }}">
                @csrf
                <div class="acss-master-form-shell">
                @include('kaprodi.periode.partials.form-fields')
                </div>
                <div class="form-actions form-actions--inline ">
                    <button type="button" class="button button--muted button--inline" data-periode-create-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Periode</button>
                </div>
            </form>
        </div>
    </div>

<script>
(() => {
    const createModal = document.querySelector('[data-periode-create-modal]');
    const toggleModal = (modal, show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-periode-create-open]')) {
            toggleModal(createModal, true);
        }
        if (event.target.closest('[data-periode-create-close]')) {
            toggleModal(createModal, false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(createModal, false);
    });
})();
</script>
@endsection