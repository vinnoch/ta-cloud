@extends('layouts.app')
@section('content')
    <div id="feedback">
        @if (session('success')) <div class="notice notice--success">{{ session('success') }}</div> @endif
        @if (session('info')) <div class="notice notice--info">{{ session('info') }}</div> @endif
    </div>
    @include('partials.page-header', [
        'title' => 'Data Non-Skripsi',
        'eyebrow' => 'Mahasiswa • Records',
        'actions' => [
            ['href' => route('mahasiswa.skripsi.create', ['type' => 'non_skripsi']), 'label' => 'Tambah Data Baru']
        ]
    ])
    <section class="card-list">
        @forelse ($records as $record)
            <article class="list-card">
                <div>
                    <h4>{{ $record->summary }}</h4>
                    <p>{{ \Illuminate\Support\Str::limit($record->abstract, 100) }}</p>
                </div>
                <div class="status-stack">
                    <a class="text-link" href="{{ route('mahasiswa.non-skripsi.show', $record) }}">Detail</a>
                    <a class="text-link" href="{{ route('mahasiswa.skripsi.create', ['type' => 'non_skripsi']) }}">Edit</a>
                </div>
            </article>
        @empty
            <div class="empty-state">
                Belum ada data non-skripsi.
                <div class="acss-link-gap-top">
                    <a class="button button--inline" href="{{ route('mahasiswa.skripsi.create', ['type' => 'non_skripsi']) }}">Isi Form Non-Skripsi</a>
                </div>
            </div>
        @endforelse
    </section>
@endsection
