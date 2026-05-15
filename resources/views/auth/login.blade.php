<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Masuk TA Cloud' }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|manrope:700,800|jetbrains-mono:700"
        rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="ta-body">
@php
    $translateAuthError = function (string $message): string {
        return match ($message) {
            'These credentials do not match our records.' => 'Email atau password tidak sesuai.',
            'The email field is required.' => 'Email wajib diisi.',
            'The password field is required.' => 'Password wajib diisi.',
            'The email field must be a valid email address.' => 'Format email tidak valid.',
            default => $message,
        };
    };
@endphp

    <main class="auth-page auth-page--single">
        <section class="auth-card auth-card--login-single">
            <div class="auth-brand">
                <img class="auth-brand__logo" src="{{ asset('images/ukwk-logo.png') }}" alt="Logo UKWK">
                
                <h1>TA Cloud UKWK</h1>
            </div>

            @if (session('status'))
                <div class="auth-status">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="auth-form" id="login-form">
                @csrf

                @if (! empty($testAccounts))
                    <label>
                        <span>Shortcut Role</span>
                        <select id="login-shortcut-select" class="auth-shortcut-select">
                            <option value="">Pilih role</option>
                            @foreach ($testAccounts as $index => $account)
                                <option value="{{ $index }}" data-email="{{ $account['email'] }}" data-password="{{ $account['password'] }}">
                                    {{ $account['role'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label>
                    <span>Email</span>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        autocomplete="username">
                </label>
                @error('email')
                    <p class="auth-error">{{ $translateAuthError($message) }}</p>
                @enderror

                <label>
                    <span>Password</span>
                    <div class="password-field">
                        <input id="password" type="password" name="password" required autocomplete="current-password">
                        <button class="password-toggle" type="button" data-password-toggle
                            data-password-target="password" aria-label="Tampilkan password" aria-pressed="false">
                            <span class="sr-only password-toggle__show">Tampilkan password</span>
                            <span class="sr-only password-toggle__hide">Sembunyikan password</span>
                            <svg class="password-toggle__icon password-toggle__icon--show" viewBox="0 0 24 24"
                                aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                            <svg class="password-toggle__icon password-toggle__icon--hide" viewBox="0 0 24 24"
                                aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3l18 18" />
                                <path d="M10.6 10.7A3 3 0 0 0 12 15a3 3 0 0 0 2.1-.9" />
                                <path d="M9.4 5.2A11 11 0 0 1 12 5c6.5 0 10 7 10 7a18.7 18.7 0 0 1-4 4.8" />
                                <path d="M6.6 6.7A18.4 18.4 0 0 0 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.5" />
                            </svg>
                        </button>
                    </div>
                </label>
                @error('password')
                    <p class="auth-error">{{ $translateAuthError($message) }}</p>
                @enderror

                <label class="auth-check">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>

                <button class="button button--primary auth-submit" type="submit">Masuk</button>
            </form>

            <div class="auth-divider">
                <span>Atau</span>
            </div>

            <button type="button" class="button button--google" onclick="alert('Google OAuth integration is in development')">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC04" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                <span>Masuk dengan Google</span>
            </button>

        </section>
    </main>
</body>

</html>
