<article class="info-card {{ $variant ?? '' }}">
    @if (!empty($eyebrow))
        <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    <h3>{{ $title }}</h3>
    @if (!empty($description))
        <p>{{ $description }}</p>
    @endif
    @if (!empty($meta))
        <ul class="info-card__meta">
            @foreach ($meta as $item)
                <li><strong>{{ $item['label'] }}:</strong> {{ $item['value'] }}</li>
            @endforeach
        </ul>
    @endif
</article>
