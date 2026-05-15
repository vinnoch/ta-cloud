<article class="metric-card">
    <span class="metric-card__label">{{ $label }}</span>
    <strong>{{ $value }}</strong>
    @if (!empty($hint))
        <small>{{ $hint }}</small>
    @endif
</article>
