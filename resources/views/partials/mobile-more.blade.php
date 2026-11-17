<dialog id="mobile-more-menu" class="mobile-more" aria-labelledby="mobile-more-title" data-mobile-menu>
    <div class="mobile-more__handle" aria-hidden="true"></div>
    <header class="mobile-more__header">
        <div>
            <p class="mobile-more__eyebrow">{{ $branding['name'] }}</p>
            <h2 id="mobile-more-title">Lainnya</h2>
        </div>
        <button class="mobile-more__close" type="button" aria-label="Tutup menu" data-mobile-menu-close>
            @include('partials.icons.x')
        </button>
    </header>

    <nav class="mobile-more__nav" aria-label="Semua menu">
        @foreach ($navItems as $item)
            @php($children = $item['children'] ?? [])
            @if ($children)
                <section class="mobile-more__group" aria-labelledby="mobile-more-group-{{ $loop->index }}">
                    <a id="mobile-more-group-{{ $loop->index }}" class="mobile-more__group-title {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" href="{{ $item['href'] }}">
                        <span class="mobile-more__icon" aria-hidden="true">@include($item['icon'])</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                    <div class="mobile-more__children">
                        @foreach ($children as $child)
                            <a class="mobile-more__link {{ request()->routeIs($child['active']) ? 'is-active' : '' }}" href="{{ $child['href'] }}" @if(request()->routeIs($child['active'])) aria-current="page" @endif>
                                <span>{{ $child['label'] }}</span><span aria-hidden="true">›</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @else
                <a class="mobile-more__link mobile-more__link--top {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" href="{{ $item['href'] }}" @if(request()->routeIs($item['active'])) aria-current="page" @endif>
                    <span class="mobile-more__icon" aria-hidden="true">@include($item['icon'])</span>
                    <span>{{ $item['label'] }}</span><span aria-hidden="true">›</span>
                </a>
            @endif
        @endforeach
    </nav>

    <footer class="mobile-more__footer">
        <a class="mobile-more__profile" href="{{ route('profile.edit') }}">
            <span class="mobile-more__icon" aria-hidden="true">@include('partials.icons.edit')</span>
            <span>Edit Profil</span><span aria-hidden="true">›</span>
        </a>
        @foreach ($navFooterItems as $item)
            @if (($item['label'] ?? '') === 'Keluar' && auth()->check())
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="mobile-more__logout" type="submit">
                        <span class="mobile-more__icon" aria-hidden="true">@include($item['icon'])</span>
                        <span>{{ $item['label'] }}</span>
                    </button>
                </form>
            @endif
        @endforeach
    </footer>
</dialog>
