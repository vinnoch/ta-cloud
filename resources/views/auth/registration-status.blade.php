<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Status Pendaftaran — {{ $branding['name'] }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="ta-body">
    @php
        $status = $registrationStatus ?? 'pending';
        $isRejected = $status === 'rejected';
    @endphp
    <main class="auth-page auth-page--single" style="background-image: linear-gradient(180deg, rgba(255,255,255,0.58) 0%, rgba(255,255,255,0.76) 48%, rgba(254,249,239,0.96) 100%), radial-gradient(circle at top left, rgba(0,40,142,0.08), transparent 32%), url('/images/login-wave-ukwk.png'), linear-gradient(180deg, #fffef9 0%, var(--paper) 100%)">
        <section class="auth-card registration-status-card" aria-labelledby="registration-status-title">
            <div class="registration-status-icon {{ $isRejected ? 'is-rejected' : '' }}" aria-hidden="true">{{ $isRejected ? '!' : '✓' }}</div>
            <p class="registration-eyebrow">Status pendaftaran</p>
            <h1 id="registration-status-title">{{ $isRejected ? 'Pendaftaran ditolak' : 'Menunggu persetujuan' }}</h1>
            <p>{{ $isRejected ? 'Periksa alasan penolakan di bawah.' : 'Superadmin, Admin, atau Kaprodi program studi akan memeriksa data Anda.' }}</p>
            @if ($isRejected && filled($rejectionReason ?? null))
                <div class="notice notice--danger" role="alert"><strong>Alasan:</strong> {{ $rejectionReason }}</div>
            @else
                <div class="registration-status-steps" aria-label="Tahapan pendaftaran">
                    <span class="is-complete">Data dikirim</span><span class="is-current">Ditinjau</span><span>Akun aktif</span>
                </div>
            @endif
            <p class="auth-help">Masuk kembali dengan {{ $maskedEmail ?? 'email institusi Anda' }} untuk memeriksa status.</p>
            <a class="button button--muted auth-submit" href="{{ route('login') }}">Kembali ke halaman masuk</a>
        </section>
    </main>
</body>
</html>
