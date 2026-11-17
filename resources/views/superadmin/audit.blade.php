@extends('layouts.app')

@section('content')
    <section class="acss-crud-card acss-crud-card--topflush">
        <div class="acss-crud-head acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <h1 class="acss-page-title">Log Sistem</h1>
                <p class="acss-muted">Riwayat aktivitas penting di dalam sistem.</p>
            </div>
            <a class="button button--primary button--inline" href="{{ route('superadmin.audit.settings') }}">@include('partials.icons.settings')<span>Pengaturan Log</span></a>
        </div>

        <div class="acss-crud-body">
            <form method="GET" action="{{ route('superadmin.audit.index') }}" class="filter-bar acss-audit-filters" data-audit-filter-form>
                <label class="form-field">
                    <span class="sr-only">Rentang waktu</span>
                    <select name="days" aria-label="Rentang waktu">
                        <option value="">Semua waktu</option>
                        @foreach ([30, 60, 90] as $days)
                            <option value="{{ $days }}" @selected((int) ($filters['days'] ?? 0) === $days)>{{ $days }} hari terakhir</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-field">
                    <span class="sr-only">Topik</span>
                    <select name="topic" aria-label="Topik">
                        <option value="">Semua Topik</option>
                        @foreach ($auditTopics as $topic)
                            <option value="{{ $topic }}" @selected(($filters['topic'] ?? '') === $topic)>{{ $topic }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-field">
                    <span class="sr-only">Pengguna</span>
                    <input type="search" name="actor" value="{{ $filters['actor'] ?? '' }}" placeholder="Cari pengguna" autocomplete="off">
                </label>
                <label class="form-field acss-search-field acss-audit-search">
                    <span class="sr-only">Cari aktivitas atau pengguna</span>
                    <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Cari aktivitas atau pengguna">
                </label>
                <div class="acss-audit-filter-actions">
                    <button class="button button--muted button--inline" type="reset" data-audit-filter-reset hidden>Reset</button>
                </div>
            </form>

            <div data-audit-results aria-live="polite">
                <div class="table-shell">
                    @if ($logs->isNotEmpty())
                        <div class="table-shell__head table-shell__grid acss-table-cols-superadmin-audit">
                            <span>Waktu</span><span>Pengguna</span><span>Topik</span><span>Konteks</span><span>Sumber</span>
                        </div>
                    @endif
                    @forelse ($logs as $log)
                        @php
                            $display = $log->display;
                        @endphp
                        <article class="table-shell__row acss-audit-entry acss-table-cols-superadmin-audit" role="button" tabindex="0" aria-expanded="false" aria-controls="audit-detail-{{ $log->id }}" data-audit-row-toggle>
                            <div class="table-shell__cell acss-audit-time">
                                <strong>{{ $log->created_at->diffForHumans() }}</strong>
                                <span>{{ $log->created_at->translatedFormat('d F Y') }}</span>
                                <small>{{ $log->created_at->format('H:i') }} WIB</small>
                            </div>
                            <div class="table-shell__cell acss-audit-actor">
                                <span class="acss-audit-avatar" aria-hidden="true">{{ str($display['actor'])->substr(0, 1)->upper() }}</span>
                                <span class="acss-audit-actor__identity">
                                    <strong>{{ $display['actor'] }}</strong>
                                    @if ($log->actor_email)
                                        <small>{{ $log->actor_email }}</small>
                                    @else
                                        <small>Sistem</small>
                                    @endif
                                </span>
                            </div>
                            <div class="table-shell__cell acss-audit-activity">
                                <span class="acss-topic-chip">{{ $display['category'] }}</span>
                                <small>{{ $display['summary'] }}</small>
                            </div>
                            <div class="table-shell__cell acss-audit-context">
                                <strong>{{ $display['target'] }}</strong>
                                <small>{{ $display['reference'] }}</small>
                            </div>
                            <div class="table-shell__cell acss-audit-source">
                                <strong>{{ $display['ip_address'] ?? '—' }}</strong>
                            </div>
                            <dl class="acss-audit-details" id="audit-detail-{{ $log->id }}" hidden>
                                <div><dt>Sebelum</dt><dd>{{ $display['before'] ? json_encode($display['before'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—' }}</dd></div>
                                <div><dt>Sesudah</dt><dd>{{ $display['after'] ? json_encode($display['after'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '—' }}</dd></div>
                                <div><dt>IP</dt><dd>{{ $display['ip_address'] ?? '—' }}</dd></div>
                                <div><dt>Referensi</dt><dd>{{ $display['reference'] }}</dd></div>
                            </dl>
                        </article>
                    @empty
                        <div class="empty-state">Belum ada log sistem.</div>
                    @endforelse
                </div>
                <div class="pagination-shell acss-pagination-spacer">{{ $logs->links() }}</div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        const auditFilterForm = document.querySelector('[data-audit-filter-form]');
        const auditFilterReset = document.querySelector('[data-audit-filter-reset]');
        const auditResults = document.querySelector('[data-audit-results]');
        let auditFilterTimer;
        let auditFilterRequest;

        const loadAuditResults = async (url = null) => {
            if (!auditFilterForm || !auditResults || !auditFilterReset) return;

            const params = new URLSearchParams(new FormData(auditFilterForm));
            [...params].forEach(([key, value]) => {
                if (!String(value).trim()) params.delete(key);
            });

            if (url) {
                const page = new URL(url, window.location.origin).searchParams.get('page');
                if (page) params.set('page', page);
            }

            const requestUrl = params.size ? `${auditFilterForm.action}?${params}` : auditFilterForm.action;
            auditFilterRequest?.abort();
            const request = new AbortController();
            auditFilterRequest = request;
            auditResults.setAttribute('aria-busy', 'true');

            try {
                const response = await fetch(requestUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    signal: request.signal,
                });
                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const document = new DOMParser().parseFromString(await response.text(), 'text/html');
                const nextResults = document.querySelector('[data-audit-results]');
                if (!nextResults) throw new Error('Audit results unavailable');

                auditResults.innerHTML = nextResults.innerHTML;
                auditFilterReset.hidden = params.size === 0;
                window.history.replaceState({}, '', requestUrl);
            } catch (error) {
                if (error.name !== 'AbortError') window.location.assign(requestUrl);
            } finally {
                if (auditFilterRequest === request) auditResults.removeAttribute('aria-busy');
            }
        };

        auditFilterForm?.addEventListener('submit', (event) => {
            event.preventDefault();
            loadAuditResults();
        });

        auditFilterForm?.querySelectorAll('select').forEach((select) => {
            select.addEventListener('change', () => loadAuditResults());
        });

        auditFilterForm?.querySelectorAll('input[type="search"]').forEach((input) => {
            input.addEventListener('input', () => {
                window.clearTimeout(auditFilterTimer);
                auditFilterTimer = window.setTimeout(() => loadAuditResults(), 250);
            });
        });

        auditFilterForm?.addEventListener('reset', () => {
            window.setTimeout(() => loadAuditResults(), 0);
        });

        auditResults?.addEventListener('click', (event) => {
            const link = event.target.closest('.pagination-shell a');
            if (!link) return;

            event.preventDefault();
            loadAuditResults(link.href);
        });

        const toggleAuditRow = (row) => {
            const panel = document.getElementById(row.getAttribute('aria-controls'));
            if (!panel) return;

            const isOpening = row.getAttribute('aria-expanded') !== 'true';
            panel.getAnimations().forEach((animation) => animation.cancel());
            row.setAttribute('aria-expanded', String(isOpening));

            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                panel.hidden = !isOpening;
                return;
            }

            if (isOpening) panel.hidden = false;
            const panelHeight = panel.scrollHeight;
            const openingFrames = [
                { height: '0px', opacity: 0, transform: 'translateY(-4px)' },
                { height: `${panelHeight}px`, opacity: 1, transform: 'translateY(0)' },
            ];
            const animation = panel.animate(isOpening ? openingFrames : [...openingFrames].reverse(), {
                duration: 180,
                delay: isOpening ? 35 : 0,
                easing: 'cubic-bezier(.2,.8,.2,1)',
                fill: 'both',
            });

            animation.addEventListener('finish', () => {
                if (!isOpening) panel.hidden = true;
                animation.cancel();
            }, { once: true });
        };

        document.addEventListener('click', (event) => {
            const row = event.target.closest('[data-audit-row-toggle]');
            if (row) toggleAuditRow(row);
        });

        document.addEventListener('keydown', (event) => {
            const row = event.target.closest('[data-audit-row-toggle]');
            if (!row || !['Enter', ' '].includes(event.key)) return;

            event.preventDefault();
            toggleAuditRow(row);
        });
    </script>
@endpush
