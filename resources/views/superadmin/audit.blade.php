@extends('layouts.app')

@section('content')
    <section class="acss-crud-card acss-crud-card--topflush">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Log Sistem</h1>
            </div>
        </div>
        <div class="acss-crud-body">
            <div class="table-shell">
                @if ($logs->isNotEmpty())
                    <div class="table-shell__head table-shell__grid acss-table-cols-superadmin-audit">
                        <span>Waktu</span><span>Aktivitas</span><span>User</span>
                    </div>
                @endif
                @forelse ($logs as $log)
                    <div class="table-shell__row table-shell__grid acss-table-cols-superadmin-audit">
                        <div class="table-shell__cell"><strong>{{ $log->created_at->translatedFormat('d M Y') }}</strong><small>{{ $log->created_at->format('H:i:s') }} WIB</small></div>
                        <div class="table-shell__cell"><span class="pill pill--blue">{{ str($log->action)->replace(['.', '_'], ' ')->title() }}</span></div>
                        <div class="table-shell__cell">{{ $log->actor?->name ?? $log->actor_email ?? 'Terminal' }}</div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada log sistem.</div>
                @endforelse
            </div>
            <div class="pagination-shell acss-pagination-spacer">{{ $logs->links() }}</div>
        </div>
    </section>
@endsection
