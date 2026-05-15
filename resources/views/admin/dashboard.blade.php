@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Dashboard Admin',
        'eyebrow' => 'Admin Workspace',
        'description' => 'Workspace global untuk mengelola master data kampus, akses, struktur akademik, dan template lintas program studi.',
    ])

    <section class="stat-grid">
        @foreach ($stats as $stat)
            @include('partials.cards.metric', $stat)
        @endforeach
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="section-heading"><div><h3>Prioritas Administratif</h3></div></div>
            <div class="card-list">
                @foreach ($ops as $item)
                    <article class="list-card">
                        <div>
                            <h4>{{ $item['title'] }}</h4>
                            <p>{{ $item['description'] }}</p>
                        </div>
                        <div class="status-stack">
                            <span class="pill">{{ $item['status'] }}</span>
                        </div>
                    </article>
                @endforeach
            </div>
        </article>

        <aside class="stack-list">
            @foreach ($cards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </aside>
    </section>
@endsection
