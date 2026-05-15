@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Edit Profil</h1>
                <p class="acss-muted mt-1">Perbarui nama, email, identitas, dan password akun Anda.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="form-grid" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="two-column">
                    <label class="form-field">
                        <span>Nama</span>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="form-field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                </div>

                @if ($user->role === 'mahasiswa')
                    <label class="form-field">
                        <span><span class="u-upper">NIM</span></span>
                        <input type="text" name="nim" value="{{ old('nim', $user->nim) }}">
                        @error('nim') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                @endif

                @if ($user->role === 'dosen')
                    <label class="form-field">
                        <span><span class="u-upper">NIDN/NIP</span></span>
                        <input type="text" name="nidn_nip" value="{{ old('nidn_nip', $user->nidn_nip) }}">
                        @error('nidn_nip') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                @endif

                <div class="two-column">
                    <label class="form-field">
                        <span>Password Baru</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti">
                        @error('password') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="form-field">
                        <span>Konfirmasi Password Baru</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password">
                    </label>
                </div>

                <div class="acss-form-actions">
                    <button class="button button--inline" type="submit">Simpan Profil</button>
                    <button class="button button--muted button--inline" type="button" onclick="window.history.back()">Batal</button>
                </div>
            </form>
        </div>
    </section>
@endsection
