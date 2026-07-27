@extends('layouts.app')

@section('content')
    <section class="acss-crud-card acss-crud-card--topflush">
        <div class="acss-crud-head"><h1 class="acss-page-title">Database & Server</h1></div>
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__head"><h2 class="acss-card-title">Informasi Database</h2></div>
        <div class="acss-section-card__body acss-system-info-grid">
            <div class="acss-info-item"><span>Status</span><strong class="{{ $database['status'] === 'Terkoneksi' ? 'u-text-success' : 'u-text-danger' }}">{{ $database['status'] }}</strong></div>
            <div class="acss-info-item"><span>Driver</span><strong>{{ str($database['driver'])->upper() }}</strong></div>
            <div class="acss-info-item"><span>Database</span><strong>{{ $database['name'] }}</strong></div>
            <div class="acss-info-item"><span>Versi Server</span><strong>{{ $database['version'] }}</strong></div>
        </div>
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__head"><h2 class="acss-card-title">Informasi Server</h2></div>
        <div class="acss-section-card__body acss-system-info-grid">
            <div class="acss-info-item"><span>PHP</span><strong>{{ $server['php'] }}</strong></div>
            <div class="acss-info-item"><span>Laravel</span><strong>{{ $server['laravel'] }}</strong></div>
            <div class="acss-info-item"><span>Environment</span><strong>{{ str($server['environment'])->title() }}</strong></div>
            <div class="acss-info-item"><span>Zona Waktu</span><strong>{{ $server['timezone'] }}</strong></div>
        </div>
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__head"><h2 class="acss-card-title">Google Authentication</h2></div>
        <div class="acss-section-card__body acss-system-info-grid">
            <div class="acss-info-item"><span>URL Login</span><code class="acss-system-url">{{ $google['login'] }}</code></div>
            <div class="acss-info-item"><span>URL Callback</span><code class="acss-system-url">{{ $google['callback'] }}</code></div>
            <div class="acss-info-item"><span>Domain Diizinkan</span><strong>{{ '@'.$google['domain'] }}</strong></div>
        </div>
    </section>
@endsection
