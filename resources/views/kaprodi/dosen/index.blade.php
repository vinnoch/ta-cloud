@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.dosen.index') }}">
        <div class="acss-crud-head acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <h1 class="acss-page-title">{{ $heading }}</h1>
                <p class="acss-muted " id="count-text">{{ $dosen->total() }} akun dosen ditemukan.</p>
            </div>
            <div class="acss-crud-head__actions">
                <button type="button" class="button button--inline" data-dosen-create-open>@include('partials.icons.plus')<span>Tambah Dosen</span></button>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="filter-form" method="GET" action="{{ route('kaprodi.dosen.index') }}">
                    <input type="hidden" id="sort-input" name="sort" value="{{ $sort ?? '' }}">
                    <input type="hidden" id="direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
                <label class="form-field acss-search-field">
                    @include('kaprodi.partials.archive-status-filter', ['label' => 'Cari Dosen', 'status' => $status ?? 'active', 'routeName' => 'kaprodi.dosen.index', 'archivedCount' => $archivedCount ?? 0])
                    <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari nama, NIDN / NIP, atau email">
                </label>
                
            </form>

            <div id="table-wrapper">@include('kaprodi.dosen.partials.table', ['dosen' => $dosen, 'sort' => $sort ?? '', 'direction' => $direction ?? 'desc'])</div>
            <div id="pagination-wrapper" class="acss-pagination-spacer">@include('kaprodi.dosen.partials.pagination', ['dosen' => $dosen])</div>
        </div>
        <div class="stack-list" style="display:none"></div>
    </section>



    
    <div class="acss-modal" data-dosen-create-modal hidden>
        <div class="acss-modal__backdrop" data-dosen-create-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambah Dosen</h3>
                </div>
                <button type="button" class="acss-modal__close" data-dosen-create-close aria-label="Tutup">×</button>
            </div>
            <form id="dosen-index-create-form" class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.dosen.store') }}">
                @csrf
                <div class="notice notice--danger">Dosen tidak boleh duplikat. Pastikan NIDN / NIP belum terdaftar.</div>
                <div class="acss-master-form-shell">
                @include('kaprodi.dosen.partials.form-fields', [
                    'dosen' => new \App\Models\User(),
                    'passwordRequired' => true,
                ])
                </div>
                <div class="pill-row">
                    <span class="pill">Login enabled</span>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-dosen-create-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Dosen</button>
                </div>
            </form>
        </div>
    </div>
    <div class="acss-modal" data-dosen-edit-modal hidden>
        <div class="acss-modal__backdrop" data-dosen-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Dosen</h3>
                </div>
                <button type="button" class="acss-modal__close" data-dosen-edit-close aria-label="Tutup">×</button>
            </div>
            <form id="dosen-index-edit-form" class="acss-form-stack-tight" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                <label class="form-field">
                    <span>Nama Dosen</span>
                    <input type="text" name="name" data-dosen-edit-name required>
                </label>
                <label class="form-field">
                    <span><span class="u-upper">NIDN / NIP</span></span>
                    <input type="text" name="nidn_nip" data-dosen-edit-nidn>
                </label>
                <label class="form-field">
                    <span>Email Login</span>
                    <input type="email" name="email" data-dosen-edit-email required>
                </label>
                <label class="form-field">
                    <span>Password Baru</span>
                    <div class="password-field">
                        <input id="dosen-index-password" type="password" name="password" placeholder="Kosongkan jika tidak diganti">
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="dosen-index-password" aria-label="Tampilkan password" aria-pressed="false">
                            <span class="sr-only password-toggle__show">Tampilkan password</span>
                            <span class="sr-only password-toggle__hide">Sembunyikan password</span>
                            <svg class="password-toggle__icon password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" /><circle cx="12" cy="12" r="3" /></svg>
                            <svg class="password-toggle__icon password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18" /><path d="M10.6 10.7A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9" /><path d="M9.4 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-4 4.8" /><path d="M6.6 6.7A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.5" /></svg>
                        </button>
                    </div>
                </label>
                <label class="form-field">
                    <span>Konfirmasi Password</span>
                    <div class="password-field">
                        <input id="dosen-index-password-confirmation" type="password" name="password_confirmation" placeholder="Ulangi password baru jika diganti">
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="dosen-index-password-confirmation" aria-label="Tampilkan password" aria-pressed="false">
                            <span class="sr-only password-toggle__show">Tampilkan password</span>
                            <span class="sr-only password-toggle__hide">Sembunyikan password</span>
                            <svg class="password-toggle__icon password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" /><circle cx="12" cy="12" r="3" /></svg>
                            <svg class="password-toggle__icon password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18" /><path d="M10.6 10.7A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9" /><path d="M9.4 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-4 4.8" /><path d="M6.6 6.7A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.5" /></svg>
                        </button>
                    </div>
                </label>
                </div>
                <div class="pill-row">
                    <span class="pill">Login enabled</span>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-dosen-edit-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

@include('partials.ajax-list-script', [
    'rootId' => 'list-root',
    'formId' => 'filter-form',
    'searchInputId' => 'search-input',
    'tableWrapperId' => 'table-wrapper',
    'paginationWrapperId' => 'pagination-wrapper',
    'countTextId' => 'count-text',
])


<script>
(() => {
    const form = document.getElementById('filter-form');
    const sortInput = document.getElementById('sort-input');
    const directionInput = document.getElementById('direction-input');
    const tableWrapper = document.getElementById('table-wrapper');
    const editModal = document.querySelector('[data-dosen-edit-modal]');
    const createModal = document.querySelector('[data-dosen-create-modal]');
    const editForm = document.getElementById('dosen-index-edit-form');
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
        const editButton = event.target.closest('[data-dosen-edit-open]');
        if (editButton && editForm) {
            editForm.action = editButton.dataset.action || '';
            editForm.querySelector('[data-dosen-edit-name]').value = editButton.dataset.name || '';
            editForm.querySelector('[data-dosen-edit-email]').value = editButton.dataset.email || '';
            editForm.querySelector('[data-dosen-edit-nidn]').value = editButton.dataset.nidn || '';
            const password = editForm.querySelector('#dosen-index-password');
            const confirmation = editForm.querySelector('#dosen-index-password-confirmation');
            if (password) password.value = '';
            if (confirmation) confirmation.value = '';
            toggleModal(editModal, true);
        }
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-dosen-edit-close]') || event.target.closest('[data-dosen-create-close]')) {
            toggleModal(event.target.closest('.acss-modal') || editModal || createModal, false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(editModal, false);
    });

    const createBtn = document.querySelector('[data-dosen-create-open]');
    if (createBtn) {
        createBtn.addEventListener('click', () => toggleModal(createModal, true));
    }
})();
</script>
@endsection
