@include('partials.page-header', [
    'title' => $title,
    'eyebrow' => $eyebrow ?? null,
    'description' => $description ?? null,
    'actions' => $actions ?? [],
])

@if (!empty($stats ?? []))
    <section class="stat-grid">
        @foreach ($stats as $stat)
            @include('partials.cards.metric', $stat)
        @endforeach
    </section>
@endif

<section class="content-grid">
    <article class="card">
        <div class="section-heading">
            <div>
                <h3>{{ $tableTitle ?? 'Daftar Data' }}</h3>
            </div>
        </div>

        @include('partials.tables.data-table', [
            'cols' => $cols,
            'columns' => $columns,
            'rows' => $rows,
        ])
    </article>

    @if (!empty($sideCards ?? []))
        <aside class="stack-list">
            @foreach ($sideCards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </aside>
    @endif
</section>
