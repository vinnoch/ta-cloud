<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk' }} — {{ $branding['name'] }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:700,800|jetbrains-mono:700"
        rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="ta-body">
    <main class="auth-page auth-page--single" style="background-image: linear-gradient(180deg, rgba(255,255,255,0.58) 0%, rgba(255,255,255,0.76) 48%, rgba(254,249,239,0.96) 100%), radial-gradient(circle at top left, rgba(0,40,142,0.08), transparent 32%), url('/images/login-wave-ukwk.png'), linear-gradient(180deg, #fffef9 0%, var(--paper) 100%)">
        <section class="auth-card auth-card--login-single">
            <div class="auth-brand">
                <img class="auth-brand__logo auth-brand__logo--horizontal" src="{{ asset('images/logo-horizontal-warna.png') }}" alt="Universitas Katolik Widya Karya">
                
                <h1>{{ $branding['name'] }}</h1>
            </div>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <a href="{{ route('auth.google') }}" class="button button--google">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC04" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </a>
            <p class="auth-registration-link">Gunakan akun @widyakarya.ac.id</p>

            @if ($demoRoles)
                <div class="demo-login" aria-labelledby="demo-login-title">
                    <p id="demo-login-title">Mode Demo</p>
                    <div class="demo-login__roles">
                        @foreach ($demoRoles as $role)
                            <form method="POST" action="{{ route('demo.login', $role) }}">
                                @csrf
                                <button class="button demo-login__button" type="submit">{{ ucfirst($role) }}</button>
                            </form>
                        @endforeach
                    </div>
                </div>
            @endif

        </section>
    </main>
</body>

</html>
