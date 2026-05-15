@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Frontend Coverage Overview',
        'eyebrow' => 'TA Cloud • Role Switchboard',
        'description' => 'Pusat navigasi untuk seluruh area frontend: mahasiswa, dosen, kaprodi, library, serta dua prototype screen awal yang sudah dipoles.',
    ])

    <section class="hero-links">
        @foreach ($roleCards as $card)
            <a class="surface-link" href="{{ $card['href'] }}">
                <span class="feature-card__tag">{{ $card['tag'] }}</span>
                <h3>{{ $card['title'] }}</h3>
                <p>{{ $card['description'] }}</p>
                <small>{{ $card['hint'] }}</small>
            </a>
        @endforeach
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Core Modules</p>
                    <h3>Semua area PRD sudah punya layar</h3>
                </div>
            </div>
            <div class="feature-grid">
                @foreach ($featureCards as $feature)
                    @include('partials.cards.feature', $feature)
                @endforeach
            </div>
        </article>

        <article class="card">
            <div class="section-heading">
                <div>
                    <p class="eyebrow">Route Groups</p>
                    <h3>Struktur navigasi aplikasi</h3>
                </div>
            </div>
            <div class="card-list">
                @foreach ($routeGroups as $group)
                    <article class="list-card">
                        <div>
                            <h4>{{ $group['title'] }}</h4>
                            <p>{{ $group['description'] }}</p>
                        </div>
                        <div class="status-stack">
                            <span class="pill">{{ $group['count'] }} layar</span>
                            <small>{{ $group['scope'] }}</small>
                        </div>
                    </article>
                @endforeach
            </div>
        </article>
    </section>
@endsection
