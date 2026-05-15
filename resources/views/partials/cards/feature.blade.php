<article class="feature-card">
    @if (!empty($tag))
        <span class="feature-card__tag">{{ $tag }}</span>
    @endif
    <h4>{{ $title }}</h4>
    <p>{{ $description }}</p>
</article>
