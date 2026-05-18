<aside class="side-nav">
    <div class="brand-lockup">
        <a class="brand-mark" href="{{ route('home') }}">TA Cloud</a>
        <p class="brand-subtitle">{{ $navSubtitle ?? ($navRole === 'global' ? 'Sistem Manajemen Tugas Akhir' : strtoupper($navRole).' Workspace') }}</p>
    </div>

    <nav class="side-nav__links">
        @foreach ($navItems as $index => $item)
            @php
                $isActive = request()->routeIs($item['active']);
                $children = $item['children'] ?? [];
                $groupId = 'side-group-'.($index + 1);
            @endphp

            @if (!empty($children))
                <details class="side-group {{ $isActive ? 'is-active' : '' }}" {{ $isActive ? 'open' : '' }}>
                    <summary class="side-link side-group__trigger {{ $isActive ? 'is-active' : '' }}">
                        <span class="side-link__icon">@include($item['icon'])</span>
                        <span>{{ $item['label'] }}</span>
                        <span class="side-group__chevron" aria-hidden="true">▾</span>
                    </summary>
                    <div class="side-group__children" id="{{ $groupId }}">
                        @foreach ($children as $child)
                            <a class="side-sublink {{ request()->routeIs($child['active']) ? 'is-active' : '' }}" href="{{ $child['href'] }}">
                                <span>{{ $child['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </details>
            @else
                <a class="side-link {{ $isActive ? 'is-active' : '' }}" href="{{ $item['href'] }}">
                    <span class="side-link__icon">@include($item['icon'])</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </nav>

    <div class="side-nav__footer">
        @if ($primaryCta)
            <a class="primary-cta" href="{{ $primaryCta['href'] }}">{{ $primaryCta['label'] }}</a>
        @endif

        @foreach ($navFooterItems as $item)
            @if (($item['label'] ?? '') === 'Keluar' && auth()->check())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="side-link side-link--footer is-danger side-link--button" type="submit">
                        <span class="side-link__icon">@include($item['icon'])</span>
                        <span>{{ $item['label'] }}</span>
                    </button>
                </form>
            @else
                <a class="side-link side-link--footer {{ !empty($item['danger']) ? 'is-danger' : '' }}" href="{{ $item['href'] }}">
                    <span class="side-link__icon">@include($item['icon'])</span>
                    <span>{{ $item['label'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</aside>
