@props([
    'label',
    'value',
    'hint' => null,
    'featured' => false,
    'href' => null,
    'icon' => null,
])

@php($tag = $href ? 'a' : 'article')
<{{ $tag }} @if($href) href="{{ $href }}" @endif class="acss-dashboard-metric {{ $featured ? 'is-featured' : '' }} {{ $href ? 'is-link' : '' }}">
    <div class="acss-dashboard-metric__top">
        <span class="acss-dashboard-metric__label">{{ $label }}</span>
        <span class="acss-dashboard-metric__arrow" aria-hidden="true">↗</span>
    </div>
    <strong class="acss-dashboard-metric__value">
        @if ($icon)<span class="acss-dashboard-metric__status-icon">@include($icon)</span><span class="sr-only">{{ $value }}</span>@else{{ $value }}@endif
    </strong>
    @if ($hint)
        <p class="acss-dashboard-metric__hint">{{ $hint }}</p>
    @endif
</{{ $tag }}>
