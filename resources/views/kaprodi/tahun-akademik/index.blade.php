@extends('layouts.app')
@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.tahun-akademik.index') }}">
        <div class="acss-crud-head acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <h1 class="acss-page-title">Tahun Akademik</h1>
                <p class="acss-muted " id="count-text">{{ $tahunAkademik->total() }} tahun akademik ditemukan.</p>
            </div>
            <div class="acss-crud-head__actions">
                <button type="button" class="button button--inline" data-ta-create-open>@include('partials.icons.plus')<span>Tambah Tahun Akademik</span></button>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="filter-form" method="GET" action="{{ route('kaprodi.tahun-akademik.index') }}">
                    <input type="hidden" id="sort-input" name="sort" value="{{ $sort ?? '' }}">
                    <input type="hidden" id="direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Tahun</span>
                    <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari nama tahun">
                </label>
                
            </form>

            <div id="table-wrapper">@include('kaprodi.tahun-akademik.partials.table', ['tahunAkademik' => $tahunAkademik, 'sort' => $sort ?? '', 'direction' => $direction ?? 'desc'])</div>
            <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.tahun-akademik.partials.pagination', ['tahunAkademik' => $tahunAkademik])</div>
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
    const editModal = document.querySelector('[data-ta-edit-modal]');
    const editForm = document.getElementById('ta-edit-form');
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
        const editButton = event.target.closest('[data-ta-edit-open]');
        if (editButton && editForm) {
            editForm.action = editButton.dataset.action || '';
            editForm.querySelector('[data-ta-edit-tahun-awal]').value = editButton.dataset.tahunAwal || '';
            editForm.querySelector('[data-ta-edit-tahun-akhir]').value = editButton.dataset.tahunAkhir || '';
            toggleModal(editModal, true);
        }
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-ta-edit-close]')) {
            toggleModal(event.target.closest('.acss-modal') || editModal, false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(editModal, false);
    });
    })();
});
</script>


    <div class="acss-modal" data-ta-edit-modal hidden>
        <div class="acss-modal__backdrop" data-ta-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Tahun Akademik</h3>
                </div>
                <button type="button" class="acss-modal__close" data-ta-edit-close aria-label="Tutup">×</button>
            </div>
            <form id="ta-edit-form" class="acss-form-stack-tight" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                <div class="acss-grid-two acss-grid-full acss-meta-grid-tight">
                    <label class="form-field acss-field-tight">
                        <span>Tahun Awal</span>
                        <input type="number" name="tahun_awal" data-ta-edit-tahun-awal placeholder="Contoh: 2026" required>
                    </label>
                    <label class="form-field acss-field-tight">
                        <span>Tahun Akhir</span>
                        <input type="number" name="tahun_akhir" data-ta-edit-tahun-akhir placeholder="Contoh: 2027" required>
                    </label>
                </div>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-ta-edit-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acss-modal" data-ta-create-modal hidden>
        <div class="acss-modal__backdrop" data-ta-create-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambah Tahun Akademik</h3>
                </div>
                <button type="button" class="acss-modal__close" data-ta-create-close aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.tahun-akademik.store') }}">
                @csrf
                <div class="acss-master-form-shell">
                @include('kaprodi.tahun-akademik.partials.form-fields')
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-ta-create-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Tahun Akademik</button>
                </div>
            </form>
        </div>
    </div>

<script>
(() => {
    const createModal = document.querySelector('[data-ta-create-modal]');
    const toggleModal = (modal, show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-ta-create-open]')) {
            toggleModal(createModal, true);
        }
        if (event.target.closest('[data-ta-create-close]')) {
            toggleModal(createModal, false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(createModal, false);
    });
})();
</script>
@endsection