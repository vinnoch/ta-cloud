@include('partials.page-header', [
    'title' => $title,
    'eyebrow' => $eyebrow ?? null,
    'description' => $description ?? null,
])

<section class="panel-grid">
    <article class="card">
        <div class="form-grid">
            @foreach ($fields as $field)
                @include('partials.forms.field', $field)
            @endforeach

            @if (!empty($pills ?? []))
                <div class="pill-row">
                    @foreach ($pills as $pill)
                        <span class="pill">{{ $pill }}</span>
                    @endforeach
                </div>
            @endif

            <button class="button" type="button">{{ $submitLabel ?? 'Simpan' }}</button>
        </div>
    </article>

    @if (!empty($sideCards ?? []))
        <aside class="stack-list">
            @foreach ($sideCards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </aside>
    @endif
</section>
