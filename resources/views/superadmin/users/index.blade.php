@extends('layouts.app')

@section('content')
    @if (session('status'))<div class="notice notice--success" role="status">{{ session('status') }}</div>@endif
    @if ($errors->any())
        <div class="notice notice--error" role="alert">
            <strong>Tindakan belum berhasil.</strong>
            <div>{{ $errors->first() }}</div>
        </div>
    @endif

    <section class="card">
        <div class="section-heading">
            <div>
                <h1 class="acss-page-title">Users</h1>
            </div>
            <div class="acss-crud-head__actions">
                <button class="button button--primary button--inline" type="button" data-account-create-open>@include('partials.icons.plus')<span>Buat Akun</span></button>
            </div>
        </div>
            <div class="pagination-shell acss-pagination-top">{{ $users->links() }}</div>
            <div class="table-shell">
                @if ($users->isNotEmpty())
                    <div class="table-shell__head table-shell__grid acss-table-cols-superadmin-users">
                        <span>Akun</span><span>Peran</span><span>Status</span><span>Login Terakhir</span>
                    </div>
                @endif
                @forelse ($users as $user)
                    <article class="table-shell__row table-shell__grid acss-table-cols-superadmin-users acss-hover-row-group user-record-card">
                        <div class="table-shell__cell user-record-card__identity">
                            <strong>{{ $user->name }}</strong><small>{{ $user->email }}</small>
                        </div>
                        <div class="table-shell__cell user-record-card__role" data-mobile-label="Peran"><span class="pill pill--blue">{{ str($user->role)->replace('_', ' ')->title() }}</span></div>
                        <div class="table-shell__cell user-record-card__status" data-mobile-label="Status"><span class="pill {{ $user->trashed() ? 'pill--muted' : 'pill--green' }}">{{ $user->trashed() ? 'Nonaktif' : 'Aktif' }}</span></div>
                        <div class="table-shell__cell user-record-card__login" data-mobile-label="Login terakhir">@if ($user->last_login_at)<strong>{{ $user->last_login_at->translatedFormat('d M Y') }}</strong><small>{{ $user->last_login_at->format('H:i') }} WIB</small>@else<span class="acss-muted">Belum pernah</span>@endif</div>
                        <details class="user-record-card__login-details">
                            <summary>Login terakhir</summary>
                            <div>@if ($user->last_login_at)<strong>{{ $user->last_login_at->translatedFormat('d M Y') }}</strong><span>{{ $user->last_login_at->format('H:i') }} WIB</span>@else<span>Belum pernah</span>@endif</div>
                        </details>
                        <div class="user-record-card__actions acss-row-actions">
                            @if ($user->trashed())
                                <form class="inline-form" method="POST" action="{{ route('superadmin.users.restore', $user->id) }}" onsubmit="return confirm('Aktifkan kembali akun ini?')">
                                    @csrf
                                    <button class="text-link acss-action-link user-record-card__primary-action" type="submit">@include('partials.icons.edit')<span>Aktifkan</span></button>
                                </form>
                                <span class="acss-action-separator user-record-card__desktop-secondary">|</span>
                                <form class="inline-form user-record-card__desktop-secondary" method="POST" action="{{ route('superadmin.users.permanently-destroy', $user->id) }}" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }} secara permanen? Tindakan ini tidak dapat dibatalkan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Hapus Permanen</span></button>
                                </form>
                                <details class="user-record-card__mobile-secondary">
                                    <summary aria-label="Tindakan lainnya">•••</summary>
                                    <form method="POST" action="{{ route('superadmin.users.permanently-destroy', $user->id) }}" onsubmit="return confirm('Hapus akun {{ addslashes($user->name) }} secara permanen? Tindakan ini tidak dapat dibatalkan.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Hapus Permanen</span></button>
                                    </form>
                                </details>
                            @else
                                <button class="text-link acss-action-link user-record-card__primary-action" type="button" data-account-edit-open data-action="{{ route('superadmin.users.update', $user) }}" data-name="{{ e($user->name) }}" data-email="{{ e($user->email) }}" data-roles='@json($user->availableRoleNames())'>@include('partials.icons.edit')<span>Edit</span></button>
                                <span class="acss-action-separator user-record-card__desktop-secondary">|</span>
                                <form class="inline-form user-record-card__desktop-secondary" method="POST" action="{{ route('superadmin.users.destroy', $user) }}" onsubmit="return confirm('Nonaktifkan akun {{ addslashes($user->name) }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Nonaktifkan</span></button>
                                </form>
                                <details class="user-record-card__mobile-secondary">
                                    <summary aria-label="Tindakan lainnya">•••</summary>
                                    <form method="POST" action="{{ route('superadmin.users.destroy', $user) }}" onsubmit="return confirm('Nonaktifkan akun {{ addslashes($user->name) }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-link text-link--danger acss-action-link" type="submit">@include('partials.icons.archive')<span>Nonaktifkan</span></button>
                                    </form>
                                </details>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="empty-state">Belum ada akun.</div>
                @endforelse
            </div>
            <div class="pagination-shell acss-pagination-spacer">{{ $users->links() }}</div>
    </section>

    <div class="acss-modal" data-account-create-modal role="dialog" aria-modal="true" aria-labelledby="account-create-modal-title" hidden>
        <div class="acss-modal__backdrop" data-account-create-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <h2 class="acss-card-title" id="account-create-modal-title">Buat Akun</h2>
                <button class="acss-modal__close" type="button" data-account-create-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('superadmin.users.store') }}" class="acss-form-stack-tight">
                @csrf
                <div class="notice notice--warning">Akun menggunakan email institusi untuk masuk melalui Google. Sistem tidak mengirim email undangan.</div>
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Nama Lengkap</span>
                        <input name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                    </label>
                    <label class="form-field">
                        <span>Email Institusi</span>
                        <input name="email" value="{{ old('email') }}" type="email" required autocomplete="email" placeholder="nama@widyakarya.ac.id">
                    </label>
                    <label class="form-field">
                        <span>Peran</span>
                        <select name="role" required>
                            @foreach ($roles as $role)<option value="{{ $role }}" @selected(old('role') === $role)>{{ str($role)->replace('_', ' ')->title() }}</option>@endforeach
                        </select>
                    </label>
                </div>
                <div class="form-actions form-actions--inline">
                    <button class="button button--muted button--inline" type="button" data-account-create-close>Batal</button>
                    <button class="button button--primary button--inline" type="submit">Buat Akun</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acss-modal" data-account-edit-modal role="dialog" aria-modal="true" aria-labelledby="account-edit-modal-title" hidden>
        <div class="acss-modal__backdrop" data-account-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <h2 class="acss-card-title" id="account-edit-modal-title">Edit User</h2>
                <button class="acss-modal__close" type="button" data-account-edit-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" class="acss-form-stack-tight" data-account-edit-form>
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                    <label class="form-field"><span>Nama</span><input data-account-edit-name disabled></label>
                    <label class="form-field"><span>Email</span><input data-account-edit-email disabled></label>
                    <fieldset class="form-field role-checkbox-group">
                        <legend>Peran</legend>
                        <div class="role-checkbox-list">
                            @foreach ($roles as $role)
                                <label><input type="checkbox" name="roles[]" value="{{ $role }}" data-account-edit-role> <span>{{ str($role)->replace('_', ' ')->title() }}</span></label>
                            @endforeach
                        </div>
                    </fieldset>
                </div>
                <div class="form-actions form-actions--inline">
                    <button class="button button--muted button--inline" type="button" data-account-edit-close>Batal</button>
                    <button class="button button--primary button--inline" type="submit">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.querySelector('[data-account-create-modal]');
            const editModal = document.querySelector('[data-account-edit-modal]');
            const toggle = (target, show) => {
                target.hidden = !show;
                document.body.classList.toggle('acss-modal-open', show);
            };

            document.addEventListener('click', (event) => {
                if (event.target.closest('[data-account-create-open]')) toggle(modal, true);
                if (event.target.closest('[data-account-create-close]')) toggle(modal, false);
                if (event.target.closest('[data-account-edit-close]')) toggle(editModal, false);

                const editButton = event.target.closest('[data-account-edit-open]');
                if (editButton) {
                    editModal.querySelector('[data-account-edit-form]').action = editButton.dataset.action;
                    editModal.querySelector('[data-account-edit-name]').value = editButton.dataset.name;
                    editModal.querySelector('[data-account-edit-email]').value = editButton.dataset.email;
                    const assignedRoles = JSON.parse(editButton.dataset.roles);
                    editModal.querySelectorAll('[data-account-edit-role]').forEach((input) => {
                        input.checked = assignedRoles.includes(input.value);
                    });
                    toggle(editModal, true);
                }
            });

            @if ($errors->has('name') || $errors->has('email'))
                toggle(modal, true);
            @endif
        })();
    </script>
@endsection
