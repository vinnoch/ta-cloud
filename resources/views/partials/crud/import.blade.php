@include('partials.page-header', [
    'title' => $title,
    'eyebrow' => $eyebrow ?? null,
    'description' => $description ?? null,
])

<section class="panel-grid">
    <article class="card">
        <div class="form-grid">
            @include('partials.forms.field', ['label' => 'Template CSV', 'value' => $templateName ?? 'template.csv'])
            @include('partials.forms.field', ['label' => 'Upload File CSV', 'placeholder' => 'Pilih file CSV'])
            @include('partials.forms.field', ['label' => 'Catatan Import', 'type' => 'textarea', 'placeholder' => $notePlaceholder ?? 'Opsional...'])
            <button class="button" type="button">{{ $submitLabel ?? 'Proses Import' }}</button>
        </div>
    </article>

    <aside class="stack-list">
        @foreach ($sideCards as $card)
            @include('partials.cards.info', $card)
        @endforeach
    </aside>
</section>
