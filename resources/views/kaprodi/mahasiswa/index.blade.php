@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card" id="list-root" data-endpoint="{{ route('kaprodi.mahasiswa.index') }}">
        <div class="acss-crud-head acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <h1 class="acss-page-title">Master Mahasiswa</h1>
                <p class="acss-muted mt-1" id="count-text">{{ $mahasiswa->total() }} akun mahasiswa ditemukan.</p>
            </div>
            <div class="acss-crud-head__actions">
                <button type="button" class="button button--inline" data-mahasiswa-create-open>@include('partials.icons.plus')<span>Tambah Mahasiswa</span></button>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" id="filter-form" method="GET" action="{{ route('kaprodi.mahasiswa.index') }}">
                    <input type="hidden" id="sort-input" name="sort" value="{{ $sort ?? '' }}">
                    <input type="hidden" id="direction-input" name="direction" value="{{ $direction ?? 'desc' }}">
                <input type="hidden" name="status" value="{{ $status ?? 'active' }}">
                <label class="form-field acss-search-field">
                    @include('kaprodi.partials.archive-status-filter', ['label' => 'Cari Mahasiswa', 'status' => $status ?? 'active', 'routeName' => 'kaprodi.mahasiswa.index', 'archivedCount' => $archivedCount ?? 0])
                    <input type="search" id="search-input" name="q" value="{{ $search }}" placeholder="Cari nama, NIM, atau email">
                </label>
                
            </form>

            <div id="table-wrapper">@include('kaprodi.mahasiswa.partials.table', ['mahasiswa' => $mahasiswa, 'sort' => $sort ?? '', 'direction' => $direction ?? 'desc'])</div>
            <div id="pagination-wrapper">@include('kaprodi.mahasiswa.partials.pagination', ['mahasiswa' => $mahasiswa])</div>
        </div>
    </section>



    
    <div class="acss-modal" data-mahasiswa-create-modal hidden>
        <div class="acss-modal__backdrop" data-mahasiswa-create-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambah Mahasiswa</h3>
                </div>
                <button type="button" class="acss-modal__close" data-mahasiswa-create-close aria-label="Tutup">×</button>
            </div>
            <form id="mahasiswa-index-create-form" class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.mahasiswa.store') }}">
                @csrf
                <div class="notice notice--danger">Mahasiswa tidak boleh duplikat. Pastikan NIM belum terdaftar.</div>
                <div class="acss-master-form-shell">
                @include('kaprodi.mahasiswa.partials.form-fields', [
                    'mahasiswa' => new \App\Models\User(),
                    'passwordRequired' => true,
                ])
                </div>
                <div class="pill-row">
                    <span class="pill">Role otomatis: MAHASISWA</span>
                    <span class="pill">Login enabled</span>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-mahasiswa-create-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Mahasiswa</button>
                </div>
            </form>
        </div>
    </div>
    <div class="acss-modal" data-mahasiswa-edit-modal hidden>
        <div class="acss-modal__backdrop" data-mahasiswa-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Mahasiswa</h3>
                </div>
                <button type="button" class="acss-modal__close" data-mahasiswa-edit-close aria-label="Tutup">×</button>
            </div>
            <form id="mahasiswa-index-edit-form" class="acss-form-stack-tight" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                <label class="form-field">
                    <span>Nama Mahasiswa</span>
                    <input type="text" name="name" data-mahasiswa-edit-name required>
                </label>
                <label class="form-field">
                    <span><span class="u-upper">NIM</span></span>
                    <input type="text" name="nim" data-mahasiswa-edit-nim required>
                </label>
                <label class="form-field">
                    <span>Email Login</span>
                    <input type="email" name="email" data-mahasiswa-edit-email required>
                </label>
                <label class="form-field">
                    <span>Password Baru</span>
                    <div class="password-field">
                        <input id="mahasiswa-index-password" type="password" name="password" placeholder="Kosongkan jika tidak diganti">
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="mahasiswa-index-password" aria-label="Tampilkan password" aria-pressed="false">
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
                        <input id="mahasiswa-index-password-confirmation" type="password" name="password_confirmation" placeholder="Ulangi password baru jika diganti">
                        <button class="password-toggle" type="button" data-password-toggle data-password-target="mahasiswa-index-password-confirmation" aria-label="Tampilkan password" aria-pressed="false">
                            <span class="sr-only password-toggle__show">Tampilkan password</span>
                            <span class="sr-only password-toggle__hide">Sembunyikan password</span>
                            <svg class="password-toggle__icon password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" /><circle cx="12" cy="12" r="3" /></svg>
                            <svg class="password-toggle__icon password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18" /><path d="M10.6 10.7A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9" /><path d="M9.4 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-4 4.8" /><path d="M6.6 6.7A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.5" /></svg>
                        </button>
                    </div>
                </label>
                </div>
                <div class="pill-row">
                    <span class="pill">Role otomatis: MAHASISWA</span>
                    <span class="pill">Login enabled</span>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-mahasiswa-edit-close>Batal</button>
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
    const editModal = document.querySelector('[data-mahasiswa-edit-modal]');
    const createModal = document.querySelector('[data-mahasiswa-create-modal]');
    const editForm = document.getElementById('mahasiswa-index-edit-form');
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
        const editButton = event.target.closest('[data-mahasiswa-edit-open]');
        if (editButton && editForm) {
            editForm.action = editButton.dataset.action || '';
            editForm.querySelector('[data-mahasiswa-edit-name]').value = editButton.dataset.name || '';
            editForm.querySelector('[data-mahasiswa-edit-nim]').value = editButton.dataset.nim || '';
            editForm.querySelector('[data-mahasiswa-edit-email]').value = editButton.dataset.email || '';
            const password = editForm.querySelector('#mahasiswa-index-password');
            const confirmation = editForm.querySelector('#mahasiswa-index-password-confirmation');
            if (password) password.value = '';
            if (confirmation) confirmation.value = '';
            toggleModal(editModal, true);
        }
    });
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-mahasiswa-edit-close]') || event.target.closest('[data-mahasiswa-create-close]')) {
            toggleModal(event.target.closest('.acss-modal') || editModal || createModal, false);
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(editModal, false);
    });

    const createBtn = document.querySelector('[data-mahasiswa-create-open]');
    if (createBtn) {
        createBtn.addEventListener('click', () => toggleModal(createModal, true));
    }
})();
</script>
@endsection
