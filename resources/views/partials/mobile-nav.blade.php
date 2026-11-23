@php
    $mobilePrimaryLabels = match ($navRole ?? 'global') {
        'superadmin' => ['Dashboard', 'Users', 'Pendaftaran'],
        'kaprodi' => ['Dashboard', 'Monitoring', 'Master Mahasiswa'],
        'dosen' => ['Dashboard', 'Tugas Akhir', 'Proposal'],
        'mahasiswa' => ['Skripsi', 'Non-Skripsi', 'Bimbingan'],
        default => collect($navItems ?? [])->pluck('label')->take(3)->all(),
    };

    $mobileNavItems = collect($navItems ?? [])->flatMap(function ($item) {
        return collect([$item])->concat($item['children'] ?? []);
    });

    $mobilePrimaryItems = collect($mobilePrimaryLabels)
        ->map(fn ($label) => $mobileNavItems->firstWhere('label', $label))
        ->filter()
        ->values();

    $mobilePrimaryIsActive = $mobilePrimaryItems->contains(
        fn ($item) => request()->routeIs($item['active'])
    );
@endphp

<nav class="mobile-bottom-nav" aria-label="Navigasi utama">
    @foreach ($mobilePrimaryItems as $item)
        <a class="mobile-bottom-nav__item {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" href="{{ $item['href'] }}" @if(request()->routeIs($item['active'])) aria-current="page" @endif>
            <span class="mobile-bottom-nav__icon" aria-hidden="true">@include($item['icon'])</span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach

    <button class="mobile-bottom-nav__item mobile-bottom-nav__more {{ $mobilePrimaryIsActive ? '' : 'is-active' }}" type="button" aria-haspopup="dialog" aria-controls="mobile-more-menu" data-mobile-menu-open>
        <span class="mobile-bottom-nav__dots" aria-hidden="true"><i></i><i></i><i></i></span>
        <span>Lainnya</span>
    </button>
</nav>

@include('partials.mobile-more', [
    'navItems' => $navItems ?? [],
    'navFooterItems' => $navFooterItems ?? [],
])
