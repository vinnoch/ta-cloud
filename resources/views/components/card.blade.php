@props([
    'title' => '',
    'subtitle' => '',
    'icon' => '',
    'badge' => '',
    'href' => '',
    'actions' => [],
    'status' => '',
    'eyebrow' => '',
])

<article class="card">
    @if ($eyebrow)
        <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    
    <div class="card-header">
        @if ($icon)
            <div class="card-icon">{!! $icon !!}</div>
        @endif
        <div class="card-title-wrap">
            @if ($href)
                <a href="{{ $href }}" class="card-title-link">
                    <h3 class="card-title">{{ $title }}</h3>
                </a>
            @else
                <h3 class="card-title">{{ $title }}</h3>
            @endif
            
            @if ($subtitle)
                <p class="card-subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        
        @if ($badge)
            <span class="pill{{ $status === 'archived' ? ' pill--muted' : '' }}">{{ $badge }}</span>
        @endif
    </div>
    
    @if (count($actions) > 0)
        <div class="card-actions">
            @foreach ($actions as $action)
                @if (isset($action['href']))
                    <a href="{{ $action['href'] }}" class="text-link{{ $action['danger'] ?? false ? ' is-danger' : '' }}">
                        {{ $action['label'] }}
                    </a>
                @else
                    <button type="button" class="text-link{{ $action['danger'] ?? false ? ' is-danger' : '' }}" onclick="{{ $action['onclick'] ?? '' }}">
                        {{ $action['label'] }}
                    </button>
                @endif
            @endforeach
        </div>
    @endif
</article>
