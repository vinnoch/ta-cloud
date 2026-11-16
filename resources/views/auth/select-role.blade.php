<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pilih Peran — {{ $branding['name'] }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="ta-body">
    <main class="auth-page auth-page--single role-selection-page" style="background-image: linear-gradient(180deg, rgba(255,255,255,0.58) 0%, rgba(255,255,255,0.76) 48%, rgba(254,249,239,0.96) 100%), radial-gradient(circle at top left, rgba(0,40,142,0.08), transparent 32%), url('/images/login-wave-ukwk.png'), linear-gradient(180deg, #fffef9 0%, var(--paper) 100%)">
        <section class="auth-card role-selection-card" aria-labelledby="role-selection-title">
            <div class="auth-brand">
                <img class="auth-brand__logo auth-brand__logo--horizontal" src="{{ asset('images/logo-horizontal-warna.png') }}" alt="Universitas Katolik Widya Karya">
                <p class="role-selection-greeting">Selamat datang, <strong>{{ request()->user()->name }}</strong></p>
                <h1 id="role-selection-title">Pilih peran</h1>
            </div>
            <form method="POST" action="{{ route('role-selection.store') }}" class="auth-form">
                @csrf
                <div class="role-choice-list" aria-label="Pilih workspace">
                    @foreach ($roles as $role)
                        <button class="role-choice" type="submit" name="role" value="{{ $role }}">
                            <span>{{ str($role)->replace('_', ' ')->title() }}</span>
                            <span class="role-choice__icon" aria-hidden="true">
                                <svg viewBox="0 0 20 20" width="18" height="18" fill="none"><path d="m7.5 5 5 5-5 5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        </button>
                    @endforeach
                </div>
            </form>
        </section>
    </main>
</body>
</html>
