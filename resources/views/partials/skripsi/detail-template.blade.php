<section class="detail-grid">
    <article class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ $student['avatar'] ?? 'AS' }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $student['name'] }}</h2>
                        <p>{{ $student['nim'] }} • {{ $student['program'] }}</p>
                    </div>
                    <span class="status-pill">{{ $student['status'] }}</span>
                </div>
                <div class="research-panel">
                    <span class="panel-label">Judul Penelitian</span>
                    <p>{{ $student['title'] }}</p>
                </div>
            </div>
        </div>
    </article>

    <aside class="card card--soft">
        <div class="section-heading">
            <div>
                <p class="eyebrow">{{ $advisor['eyebrow'] ?? 'Dosen Pembimbing' }}</p>
                <h3>{{ $advisor['name'] }}</h3>
            </div>
        </div>
        <p class="muted">{{ $advisor['nidn'] }}</p>
        <dl class="stats-list">
            <div>
                <dt>{{ $advisor['sessionsLabel'] ?? 'Total Pertemuan' }}</dt>
                <dd>{{ $advisor['sessions'] }}</dd>
            </div>
            <div>
                <dt>{{ $advisor['completionLabel'] ?? 'Persentase Selesai' }}</dt>
                <dd class="text-primary">{{ $advisor['completion'] }}</dd>
            </div>
        </dl>
    </aside>
</section>

<section class="card">
    <div class="section-heading">
        <div>
            <h3>Status Progress Skripsi</h3>
        </div>
    </div>
    <div class="phase-tracker">
        @foreach ($phases as $phase)
            <div class="phase-step {{ $phase['state'] }}">
                <div class="phase-step__bubble">
                    @if ($phase['state'] === 'done')
                        @include('partials.icons.check')
                    @elseif ($phase['state'] === 'current')
                        @include('partials.icons.phase-chat')
                    @elseif ($phase['state'] === 'review')
                        @include('partials.icons.phase-shield')
                    @else
                        @include('partials.icons.phase-flag')
                    @endif
                </div>
                <strong>{{ $phase['label'] }}</strong>
                @if (! empty($phase['caption']))
                    <span>{{ $phase['caption'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
</section>

<section class="detail-grid detail-grid--history">
    <article class="card">
        <div class="section-heading">
            <div>
                <h3>{{ $historyTitle ?? 'Histori Bimbingan' }}</h3>
            </div>
            @if (! empty($historyAction))
                <a class="text-link" href="{{ $historyAction['href'] }}">{{ $historyAction['label'] }}</a>
            @endif
        </div>

        <div class="history-table">
            <div class="history-table__head">
                <span>Tanggal</span>
                <span>Topik Sesi</span>
                <span>Catatan Pembimbing</span>
                <span>Status</span>
            </div>
            @foreach ($history as $item)
                <div class="history-table__row">
                    <div>
                        <strong>{{ $item['date'] }}</strong>
                        <small>{{ $item['time'] }}</small>
                    </div>
                    <div>
                        <strong>{{ $item['topic'] }}</strong>
                        <small>{{ $item['summary'] }}</small>
                    </div>
                    <blockquote>{{ $item['note'] }}</blockquote>
                    <div>
                        <span class="badge badge--success">{{ $item['status'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        @if (! empty($historyFooterAction))
            <a class="history-footer-link" href="{{ $historyFooterAction['href'] }}">{{ $historyFooterAction['label'] }}</a>
        @endif
    </article>

    <aside class="stacked-aside">
        @if (! empty($validation))
            <article class="card card--action">
                <div class="section-heading section-heading--light">
                    <div>
                        <h3>{{ $validation['title'] ?? 'Validasi Tahap' }}</h3>
                    </div>
                </div>
                <p>{{ $validation['message'] }}</p>
                @foreach (($validation['actions'] ?? []) as $action)
                    <a class="button button--light" href="{{ $action['href'] }}">{{ $action['label'] }}</a>
                @endforeach
                @if (empty($validation['actions']) && ! empty($validation['actionLabel']))
                    @if (! empty($validation['actionHref']))
                        <a class="button button--light" href="{{ $validation['actionHref'] }}">{{ $validation['actionLabel'] }}</a>
                    @else
                        <button class="button button--light" type="button">{{ $validation['actionLabel'] }}</button>
                    @endif
                @endif
                <small>{{ $validation['hint'] }}</small>
            </article>
        @endif

        <article class="card card--soft">
            <div class="section-heading">
                <div>
                    <h3>{{ $filesTitle ?? 'File Pendukung' }}</h3>
                </div>
            </div>
            <div class="file-list">
                @foreach ($files as $file)
                    <div class="file-item">
                        <div class="file-item__left">
                            <span class="file-item__icon">@include('partials.icons.folder')</span>
                            <span>{{ $file }}</span>
                        </div>
                        <span class="file-item__action">@include('partials.icons.download')</span>
                    </div>
                @endforeach
            </div>
        </article>

        @foreach (($extraSideCards ?? []) as $card)
            @include('partials.cards.info', $card)
        @endforeach
    </aside>
</section>
