<label class="form-field acss-field-tight">
    <span><span class="u-upper">NIDN / NIP</span></span>
    <input type="text" name="nidn_nip" value="{{ old('nidn_nip', $dosen->nidn_nip) }}" placeholder="Contoh: 0412345678 atau 198709102019031001">
    @error('nidn_nip')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

<label class="form-field acss-field-tight">
    <span>Nama Dosen</span>
    <input type="text" name="name" value="{{ old('name', $dosen->name) }}" placeholder="Contoh: Dr. Sarah Wijaya" required>
    @error('name')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

<label class="form-field acss-field-tight">
    <span>Email Login</span>
    <input type="email" name="email" value="{{ old('email', $dosen->email) }}" placeholder="nama@kampus.ac.id" required>
    @error('email')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

<label class="form-field acss-field-tight">
    <span>Password {{ $passwordRequired ? '' : 'Baru' }}</span>
    <div class="password-field">
        <input
            id="dosen-password"
            type="password"
            name="password"
            placeholder="{{ $passwordRequired ? 'Minimal 8 karakter' : 'Kosongkan jika tidak diganti' }}"
            {{ $passwordRequired ? 'required' : '' }}
        >
        <button class="password-toggle" type="button" data-password-toggle data-password-target="dosen-password" aria-label="Tampilkan password" aria-pressed="false">
            <span class="sr-only password-toggle__show">Tampilkan password</span>
            <span class="sr-only password-toggle__hide">Sembunyikan password</span>
            <svg class="password-toggle__icon password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg class="password-toggle__icon password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18" />
                <path d="M10.6 10.7A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9" />
                <path d="M9.4 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-4 4.8" />
                <path d="M6.6 6.7A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.5" />
            </svg>
        </button>
    </div>
    @error('password')
        <small class="field-error">{{ $message }}</small>
    @enderror
</label>

<label class="form-field acss-field-tight">
    <span>Konfirmasi Password</span>
    <div class="password-field">
        <input
            id="dosen-password-confirmation"
            type="password"
            name="password_confirmation"
            placeholder="{{ $passwordRequired ? 'Ulangi password' : 'Ulangi password baru jika diganti' }}"
            {{ $passwordRequired ? 'required' : '' }}
        >
        <button class="password-toggle" type="button" data-password-toggle data-password-target="dosen-password-confirmation" aria-label="Tampilkan password" aria-pressed="false">
            <span class="sr-only password-toggle__show">Tampilkan password</span>
            <span class="sr-only password-toggle__hide">Sembunyikan password</span>
            <svg class="password-toggle__icon password-toggle__icon--show" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                <circle cx="12" cy="12" r="3" />
            </svg>
            <svg class="password-toggle__icon password-toggle__icon--hide" viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 3l18 18" />
                <path d="M10.6 10.7A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9" />
                <path d="M9.4 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-4 4.8" />
                <path d="M6.6 6.7A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.5" />
            </svg>
        </button>
    </div>
</label>

