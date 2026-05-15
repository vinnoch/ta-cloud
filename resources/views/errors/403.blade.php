@extends('layouts.app')

@section('content')
    <section class="acss-crud-card" style="max-width: 600px; margin: 4rem auto; text-align: center; padding: 4rem 2rem; display: block;">
        <div style="margin: 0 auto 2rem auto; color: var(--color-danger); opacity: 0.8; width: 64px; height: 64px;">
            <svg style="display: block; width: 100%; height: 100%;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>
        
        <h1 class="acss-page-title" style="margin-bottom: 1rem; justify-content: center;">Akses Ditolak (403)</h1>
        
        <p class="acss-muted" style="margin-bottom: 2rem; line-height: 1.6;">
            Maaf, Anda tidak memiliki izin untuk mengakses halaman ini. <br>
            Hal ini mungkin terjadi karena batasan peran (role) atau karena data bukan milik Anda.
        </p>

        <div style="display: flex; gap: 1rem; justify-content: center;">
            
            <a href="{{ route('home') }}" class="button">Ke Dashboard</a>
        </div>
    </section>
@endsection
