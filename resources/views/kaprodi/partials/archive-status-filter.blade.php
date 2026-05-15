{{-- LOCKED UI PATTERN: shared archive filter pills for Kaprodi master lists. Reuse this partial. --}}
<span class="flex items-center gap-3">{{ $label }}
    @if (($archivedCount ?? 0) > 0)
        <span class="pill-row">
            <a class="pill {{ ($status ?? 'active') === 'active' ? 'pill--blue' : '' }}" href="{{ route($routeName, ['status' => 'active']) }}">Aktif</a>
            <a class="pill {{ ($status ?? 'active') === 'archived' ? 'pill--blue' : '' }}" href="{{ route($routeName, ['status' => 'archived']) }}">Arsip</a>
            <a class="pill {{ ($status ?? 'active') === 'all' ? 'pill--blue' : '' }}" href="{{ route($routeName, ['status' => 'all']) }}">Semua</a>
        </span>
    @endif
</span>