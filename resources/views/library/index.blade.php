@extends('layouts.app')

@section('content')
    <section class="three-column">
        @foreach ($libraryStats as $stat)
            @include('partials.cards.metric', $stat)
        @endforeach
    </section>

    <section class="content-grid">
        <article class="card">
            <div class="section-heading">
                <div>
                    <h1 class="acss-page-title">Library Tugas Akhir</h1>
                </div>
            </div>
            @include('partials.tables.data-table', [
                'cols' => '1.4fr 1.2fr 0.8fr 0.8fr 0.8fr',
                'columns' => ['Judul Tugas Akhir', 'Mahasiswa', 'Tipe', 'Program', 'Aksi'],
                'rows' => $rows,
                'paginator' => $skripsis,
            ])
        </article>

        <aside class="stack-list">
            @foreach ($filters as $filter)
                @include('partials.cards.info', $filter)
            @endforeach
        </aside>
    </section>
@endsection
