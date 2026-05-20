@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice notice--danger">{{ $errors->first() }}</div>
    @endif

    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Edit Profil</h1>
                <p class="acss-muted">Perbarui nama, email, identitas, dan password akun Anda.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="acss-form-stack-tight" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="acss-meta-grid-tight">
                    <label class="form-field">
                        <span>Nama</span>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" required placeholder="Nama lengkap">
                        @error('name') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="form-field">
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" required placeholder="Alamat email">
                        @error('email') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                </div>

                @if ($user->role === 'mahasiswa')
                    <label class="form-field">
                        <span>NIM</span>
                        <input type="text" name="nim" value="{{ old('nim', $user->nim) }}" placeholder="Nomor Induk Mahasiswa">
                        @error('nim') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                @endif

                @if ($user->role === 'dosen')
                    <label class="form-field">
                        <span>NIDN / NIP</span>
                        <input type="text" name="nidn_nip" value="{{ old('nidn_nip', $user->nidn_nip) }}" placeholder="Nomor Induk Dosen Nasional">
                        @error('nidn_nip') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                @endif

                <div class="acss-meta-grid-tight">
                    <label class="form-field">
                        <span>Password Baru</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Kosongkan jika tidak diganti">
                        @error('password') <small class="field-error">{{ $message }}</small> @enderror
                    </label>
                    <label class="form-field">
                        <span>Konfirmasi Password Baru</span>
                        <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Ulangi password baru">
                    </label>
                </div>

                <div class="acss-form-actions acss-form-actions--end">
                    <button class="button button--muted button--inline" type="button" onclick="window.history.back()">Batal</button>
                    <button class="button button--success button--inline" type="submit">Simpan Profil</button>
                </div>
            </form>
        </div>
    </section>
@endsection
