@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => $thesis['title'],
        'eyebrow' => 'Library Detail',
        'description' => 'Halaman detail karya akhir yang sudah dipublikasikan untuk arsip kampus dan referensi akademik.',
        'actions' => [
            ['href' => '#', 'label' => 'Unduh PDF'],
        ],
    ])

    <section class="split-layout">
        <article class="card">
            <div class="two-column">
                @foreach ($metadata as $item)
                    @include('partials.cards.info', $item)
                @endforeach
            </div>
            <div class="card" style="margin-top:1rem;padding:0;">
                <div class="section-heading" style="padding:1.25rem 1.25rem 0;">
                    <div><h3>Abstrak</h3></div>
                </div>
                <div style="padding:0 1.25rem 1.25rem;">
                    <p>{{ $thesis['abstract'] }}</p>
                </div>
            </div>
        </article>

        <aside class="stack-list">
            @include('partials.cards.info', $documentCard)
            @include('partials.cards.info', $keywordsCard)
        </aside>
    </section>
@endsection
