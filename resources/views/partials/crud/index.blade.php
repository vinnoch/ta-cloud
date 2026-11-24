@if (!empty($stats ?? []))
    <section class="stat-grid">
        @foreach ($stats as $stat)
            @include('partials.cards.metric', $stat)
        @endforeach
    </section>
@endif

<section class="card">
    <div class="section-heading">
        <div>
            <h1 class="acss-page-title">{{ $title }}</h1>
        </div>
        @if (!empty($actions ?? []))
            <div class="acss-crud-head__actions">
                @foreach ($actions as $action)
                    <a class="{{ $action['variant'] ?? 'button button--inline' }}" href="{{ $action['href'] }}">{{ $action['label'] }}</a>
                @endforeach
            </div>
        @endif
    </div>

    @include('partials.tables.data-table', [
        'cols' => $cols,
        'columns' => $columns,
        'rows' => $rows,
    ])
</section>

@if (!empty($sideCards ?? []))
    <aside class="stack-list">
        @foreach ($sideCards as $card)
            @include('partials.cards.info', $card)
        @endforeach
    </aside>
@endif
