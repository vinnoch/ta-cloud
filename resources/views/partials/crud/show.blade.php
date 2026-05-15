@include('partials.page-header', [
    'title' => $title,
    'eyebrow' => $eyebrow ?? null,
    'description' => $description ?? null,
    'actions' => $actions ?? [],
])

<section class="split-layout">
    <article class="card">
        <div class="{{ $gridClass ?? 'two-column' }}">
            @foreach ($cards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </div>

        @if (!empty($timeline ?? []))
            <div class="card acss-card-mt acss-p-5">
                <div class="section-heading">
                    <div><h3>{{ $timelineTitle ?? 'Aktivitas Terkait' }}</h3></div>
                </div>
                <div class="timeline-list">
                    @foreach ($timeline as $item)
                        <article class="timeline-item">
                            <h4>{{ $item['title'] }}</h4>
                            <p>{{ $item['description'] }}</p>
                            @if (!empty($item['meta']))
                                <small>{{ $item['meta'] }}</small>
                            @endif
                        </article>
                    @endforeach
                </div>
            </div>
        @endif
    </article>

    @if (!empty($sideCards ?? []))
        <aside class="stack-list">
            @foreach ($sideCards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </aside>
    @endif
</section>
