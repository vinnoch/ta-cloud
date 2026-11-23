<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Daftar Akun' }} — {{ $branding['name'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ta-body">
    @php
        $googleIdentity = $googleIdentity ?? null;
        $storeRoute = route('register.store');
    @endphp
    <main class="auth-page auth-page--single registration-page" style="background-image: linear-gradient(180deg, rgba(255,255,255,0.58) 0%, rgba(255,255,255,0.76) 48%, rgba(254,249,239,0.96) 100%), radial-gradient(circle at top left, rgba(0,40,142,0.08), transparent 32%), url('/images/login-wave-ukwk.png'), linear-gradient(180deg, #fffef9 0%, var(--paper) 100%)">
        <section class="auth-card registration-card" aria-labelledby="registration-title">
            <div class="auth-brand">
                <img class="auth-brand__logo auth-brand__logo--horizontal" src="{{ asset('images/logo-horizontal-warna.png') }}" alt="Universitas Katolik Widya Karya">
                <h1 id="registration-title">Daftar akun</h1>
            </div>

            @if (session('status'))
                <div class="auth-status" role="status" aria-live="polite">{{ session('status') }}</div>
            @endif

            <div class="registration-identity" role="status">
                <span class="registration-identity__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr((string) data_get($googleIdentity, 'name', 'M'), 0, 1)) }}</span>
                <span><strong>{{ data_get($googleIdentity, 'name') }}</strong><small>{{ data_get($googleIdentity, 'email') }}</small></span>
                <span class="status-pill status-pill--active">Google terverifikasi</span>
            </div>

            <form method="POST" action="{{ $storeRoute }}" class="auth-form" novalidate>
                @csrf
                <div class="registration-form-grid">
                    <div class="auth-field-group">
                        <label for="role">Peran yang diajukan</label>
                        <select id="role" name="role" required>
                            <option value="">Pilih peran</option>
                            <option value="mahasiswa" @selected(old('role') === 'mahasiswa')>Mahasiswa</option>
                            <option value="dosen" @selected(old('role') === 'dosen')>Dosen</option>
                            <option value="kaprodi" @selected(old('role') === 'kaprodi')>Kaprodi</option>
                        </select>
                        @error('role')<p class="auth-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="auth-field-group">
                        <label for="study_program_id">Program studi</label>
                        <select id="study_program_id" name="study_program_id" required @error('study_program_id') aria-invalid="true" aria-describedby="program-error" @enderror>
                            <option value="">Pilih program studi</option>
                            @foreach (($studyPrograms ?? []) as $program)
                                <option value="{{ $program->id }}" @selected(old('study_program_id') == $program->id)>{{ $program->name }}</option>
                            @endforeach
                        </select>
                        @error('study_program_id')<p class="auth-error" id="program-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="auth-field-group registration-form-grid__wide" data-role-field="mahasiswa" hidden>
                        <label for="nim">NIM</label>
                        <input id="nim" name="nim" type="text" value="{{ old('nim') }}" autocomplete="off" inputmode="numeric" @error('nim') aria-invalid="true" aria-describedby="nim-error" @enderror>
                        @error('nim')<p class="auth-error" id="nim-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                    <div class="auth-field-group registration-form-grid__wide" data-role-field="dosen,kaprodi" hidden>
                        <label for="nidn_nip">NIDN</label>
                        <input id="nidn_nip" name="nidn_nip" type="text" value="{{ old('nidn_nip') }}" autocomplete="off" @error('nidn_nip') aria-invalid="true" aria-describedby="nidn-error" @enderror>
                        @error('nidn_nip')<p class="auth-error" id="nidn-error" role="alert">{{ $message }}</p>@enderror
                    </div>
                </div>

                <button class="button button--primary registration-submit" type="submit">Kirim pendaftaran</button>
            </form>

            <form class="registration-cancel" method="POST" action="{{ route('register.cancel') }}">@csrf<button class="button button--muted" type="submit">Batal</button></form>
        </section>
    </main>
    <script>
        (() => {
            const role = document.getElementById('role');
            const fields = document.querySelectorAll('[data-role-field]');
            const syncRoleFields = () => fields.forEach((field) => {
                const active = field.dataset.roleField.split(',').includes(role.value);
                field.hidden = !active;
                field.querySelector('input').required = active;
            });

            role.addEventListener('change', syncRoleFields);
            syncRoleFields();
        })();
    </script>
</body>
</html>
