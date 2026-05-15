<section class="{{ $editorial ?? false ? 'editorial-header' : 'page-header-card' }}">
    @if (!empty($eyebrow))
        <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    <h2>{{ $title }}</h2>
    @if (!empty($description))
        <p>{{ $description }}</p>
    @endif
    @if (!empty($actions))
        <div class="page-header-actions">
            @foreach ($actions as $action)
                <a class="{{ $action['variant'] ?? 'button button--inline' }}" href="{{ $action['href'] }}">{{ $action['label'] }}</a>
            @endforeach
        </div>
    @endif
</section>
