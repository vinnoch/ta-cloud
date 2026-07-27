@extends('layouts.app')

@section('content')
    <section class="card card--profile acss-skripsi-detail-topcompact">
        <div class="profile-card acss-dashboard-header">
            <div>
                <h1 class="acss-page-title">Dashboard Superadmin</h1>
            </div>
        </div>
    </section>

    <section class="acss-dashboard-metric-grid" aria-label="Ringkasan sistem">
        @foreach ([
            ['label' => 'Akun Aktif', 'value' => $activeUserCount, 'hint' => 'Dapat mengakses sistem', 'href' => route('superadmin.users.index')],
            ['label' => 'Akun Nonaktif', 'value' => $inactiveUserCount, 'hint' => 'Dapat diaktifkan kembali', 'href' => route('superadmin.users.index')],
            ['label' => 'Log Sistem', 'value' => $auditCount, 'hint' => 'Log tercatat', 'href' => route('superadmin.audit.index')],
            ['label' => 'Database', 'value' => $databaseHealthy ? 'Terkoneksi' : 'Gangguan', 'hint' => $databaseHealthy ? 'Database terkoneksi' : 'Koneksi database terganggu', 'href' => route('superadmin.system-information'), 'icon' => $databaseHealthy ? 'partials.icons.check' : null],
        ] as $index => $stat)
            @include('kaprodi.partials.dashboard-stat-card', $stat + ['featured' => $index === 0])
        @endforeach
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__head acss-crud-head--inline">
            <div>
                <h2 class="acss-card-title">Log Sistem</h2>
            </div>
            <a class="acss-link-subtle" href="{{ route('superadmin.audit.index') }}">Lihat semua log</a>
        </div>
        <div class="acss-section-card__body">
            <div class="table-shell">
                @if ($recentLogs->isNotEmpty())
                    <div class="table-shell__head table-shell__grid acss-table-cols-superadmin-audit">
                        <span>Waktu</span><span>Aktivitas</span><span>User</span>
                    </div>
                @endif
                @forelse ($recentLogs as $log)
                    <div class="table-shell__row table-shell__grid acss-table-cols-superadmin-audit">
                        <div class="table-shell__cell"><strong>{{ $log->created_at->translatedFormat('d M Y') }}</strong><small>{{ $log->created_at->format('H:i') }} WIB</small></div>
                        <div class="table-shell__cell"><span class="pill pill--blue">{{ str($log->action)->replace(['.', '_'], ' ')->title() }}</span></div>
                        <div class="table-shell__cell">{{ $log->actor?->name ?? $log->actor_email ?? 'Terminal' }}</div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada log sistem.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
