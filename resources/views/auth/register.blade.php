<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Daftar Mahasiswa' }} — {{ $branding['name'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ta-body">
    @php
        $googleIdentity = $googleIdentity ?? null;
        $storeRoute = route('register.store');
    @endphp
    <main class="auth-page registration-page">
        <section class="auth-card registration-card" aria-labelledby="registration-title">
            <div class="auth-brand">
                <img class="auth-brand__logo" src="{{ $branding['logo_url'] }}" alt="{{ $branding['name'] }} logo">
                <h1 id="registration-title">Daftar mahasiswa</h1>
                <p>Akun aktif setelah disetujui Superadmin, Admin, atau Kaprodi.</p>
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
                        <label for="nim">NIM</label>
                        <input id="nim" name="nim" type="text" value="{{ old('nim') }}" required autocomplete="off" inputmode="numeric" @error('nim') aria-invalid="true" aria-describedby="nim-error" @enderror>
                        @error('nim')<p class="auth-error" id="nim-error" role="alert">{{ $message }}</p>@enderror
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
                </div>

                <button class="button button--primary auth-submit" type="submit">Kirim pendaftaran</button>
            </form>

            <form method="POST" action="{{ route('register.cancel') }}">@csrf<button class="button button--muted auth-submit" type="submit">Batal</button></form>
        </section>
    </main>
</body>
</html>
