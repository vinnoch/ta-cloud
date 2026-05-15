@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Library Skripsi',
        'eyebrow' => 'Common Module',
        'description' => 'Koleksi read-only dokumen final yang telah dipublikasikan oleh prodi. Layar ini menampilkan eksplorasi karya, metadata, dan akses ringkas ke detail.',
    ])

    <section class="three-column">
        @foreach ($libraryStats as $stat)
            @include('partials.cards.metric', $stat)
        @endforeach
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="section-heading">
                <div>
                    <h3>Koleksi terbaru</h3>
                </div>
            </div>
            @include('partials.tables.data-table', [
                'cols' => '1.4fr 1.2fr 0.8fr 0.8fr',
                'columns' => ['Judul', 'Mahasiswa', 'Program', 'Aksi'],
                'rows' => $rows,
            ])
        </article>

        <aside class="stack-list">
            @foreach ($filters as $filter)
                @include('partials.cards.info', $filter)
            @endforeach
        </aside>
    </section>
@endsection
