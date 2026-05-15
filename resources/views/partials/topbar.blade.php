<header class="top-bar">
    <div>
        @php
            $topbarRoleLabel = match (auth()->user()?->role) {
                'kaprodi' => 'Kaprodi',
                'dosen' => 'Dosen',
                'mahasiswa' => 'Mahasiswa',
                'admin' => 'Admin',
                default => !empty(auth()->user()?->role) ? strtoupper((string) auth()->user()?->role) : null,
            };

            $topbarHeading = $heading ?? $title ?? null;

            if (filled($topbarHeading) && filled($topbarRoleLabel)) {
                $roleWords = [
                    'KAPRODI' => ['Kaprodi'],
                    'DOSEN' => ['Dosen'],
                    'MAHASISWA' => ['Mahasiswa'],
                    'ADMIN' => ['Admin'],
                ][$topbarRoleLabel] ?? [];

                foreach ($roleWords as $roleWord) {
                    if (str_ends_with($topbarHeading, ' ' . $roleWord)) {
                        $topbarHeading = trim(substr($topbarHeading, 0, -1 * (mb_strlen($roleWord) + 1)));
                    }
                    if (str_starts_with($topbarHeading, $roleWord . ' ')) {
                        $topbarHeading = trim(substr($topbarHeading, mb_strlen($roleWord) + 1));
                    }
                }
            }

            $topbarEyebrow = $topbarRoleLabel && filled($topbarHeading)
                ? $topbarRoleLabel . ' » ' . $topbarHeading
                : ($crumbs ?? null);
        @endphp
        @if (!empty($topbarEyebrow))
            <p class="eyebrow">{{ $topbarEyebrow }}</p>
        @endif
    </div>

    @php
        $skripsiSearchEndpoint = null;
        $skripsiSearchResultsUrl = null;
        $topbarRole = auth()->user()?->role;

        if ($topbarRole === 'kaprodi' && \Illuminate\Support\Facades\Route::has('kaprodi.skripsi.index')) {
            $skripsiSearchEndpoint = route('kaprodi.skripsi.index');
            $skripsiSearchResultsUrl = route('kaprodi.skripsi.index');
        } elseif ($topbarRole === 'dosen' && \Illuminate\Support\Facades\Route::has('dosen.skripsi.search')) {
            $skripsiSearchEndpoint = route('dosen.skripsi.search');
            $skripsiSearchResultsUrl = \Illuminate\Support\Facades\Route::has('dosen.skripsi.index') ? route('dosen.skripsi.index') : $skripsiSearchEndpoint;
        } elseif ($topbarRole === 'mahasiswa' && \Illuminate\Support\Facades\Route::has('mahasiswa.skripsi.search')) {
            $skripsiSearchEndpoint = route('mahasiswa.skripsi.search');
            $skripsiSearchResultsUrl = \Illuminate\Support\Facades\Route::has('mahasiswa.skripsi.index') ? route('mahasiswa.skripsi.index') : $skripsiSearchEndpoint;
        }

        $skripsiSearchEnabled = !empty($skripsiSearchEndpoint);
    @endphp

    <div class="top-bar__actions">
        <label class="search-box acss-relative" for="ta-search" @if($skripsiSearchEnabled) data-search-endpoint="{{ $skripsiSearchEndpoint }}" data-search-results-url="{{ $skripsiSearchResultsUrl }}" @endif>
            <span class="search-box__icon">@include('partials.icons.search')</span>
            <input id="ta-search" class="ta-search" type="text" placeholder="Cari judul TA..." autocomplete="off" />
            <div id="topbar-ta-suggestions" class="reviewer-results acss-reviewer-results acss-topbar-results"></div>
        </label>
        @auth
            @php
                $unreadCount = auth()->user()->unreadNotifications()->count();
                $initials = collect(explode(' ', auth()->user()->name))
                    ->map(fn ($part) => mb_substr($part, 0, 1))
                    ->take(2)
                    ->implode('');
            @endphp
            <div class="notification-shell" data-notification-shell>
                <button
                    class="icon-button notification-button {{ $unreadCount > 0 ? 'has-unread' : '' }}"
                    type="button"
                    aria-label="Notifications" title="Notifikasi"
                    aria-expanded="false"
                    data-notification-button
                    data-unread-count="{{ $unreadCount }}"
                    data-index-url="{{ route('notifications.index') }}"
                    data-read-all-url="{{ route('notifications.read-all') }}"
                >
                    @include('partials.icons.bell')
                    <span class="notification-button__badge" data-notification-badge @hidden($unreadCount === 0)>{{ $unreadCount }}</span>
                </button>
                <div class="notification-dropdown" data-notification-dropdown hidden>
                    <div class="notification-dropdown__header">
                        <div>
                            <strong>Notifications</strong>
                            <small data-notification-summary>{{ $unreadCount }} unread</small>
                        </div>
                        <button class="notification-dropdown__action" type="button" data-notification-read-all>Tandai semua dibaca</button>
                    </div>
                    <div class="notification-dropdown__list" data-notification-list>
                        <p class="notification-dropdown__empty">Loading notifications...</p>
                    </div>
                </div>
            </div>
            
            <div class="acss-relative" data-user-menu-shell>
                <button class="user-chip acss-reset-button" type="button" aria-haspopup="true" aria-expanded="false" data-user-menu-trigger>
                    <span class="avatar-chip" aria-hidden="true">{{ $initials }}</span>
                    <span class="acss-text-left">
                        <strong>{{ collect(preg_split('/\s+/', trim(auth()->user()->name)))->take(2)->implode(' ') }}</strong>
                        <small>{{ strtoupper(auth()->user()->role) }}</small>
                    </span>
                </button>
                
                <div class="acss-user-dropdown" data-user-dropdown hidden>
                    <div class="acss-user-dropdown__body">
                        <a href="{{ route('profile.edit') }}" class="acss-user-dropdown__item" title="Edit Profil">
                            <span class="acss-user-dropdown__icon">@include('partials.icons.edit')</span>
                            <span>Edit Profil</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="acss-user-dropdown__item is-danger acss-reset-button acss-w-full">
                                <span class="acss-user-dropdown__icon">@include('partials.icons.logout')</span>
                                <span>Keluar</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @else
            <a class="button button--inline" href="{{ route('login') }}">Masuk</a>
        @endauth
    </div>
</header>





