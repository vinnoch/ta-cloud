@extends('layouts.app')
@section('content')
    <div id="feedback">
        @if (session('success')) <div class="notice notice--success">{{ session('success') }}</div> @endif
    </div>
    @include('partials.page-header', [
        'title' => 'Detail Non-Skripsi',
        'eyebrow' => 'Mahasiswa • View',
        'actions' => [
            ['href' => route('mahasiswa.skripsi.create', ['type' => 'non_skripsi']), 'label' => 'Edit Data']
        ]
    ])
    <section class="acss-stack-sections mt-4">
        <article class="card acss-section-card">
            <div class="acss-section-card__head"><div><h3 class="acss-card-title">{{ $non_skripsi->summary }}</h3></div></div>
            <div class="acss-section-card__body"><div class="content-block">
                <label class="label-muted">Abstrak</label>
                <p>{{ $non_skripsi->abstract }}</p>
            </div>
            <div class="grid-two">
                <div>
                    <label class="label-muted">Nilai Akhir</label>
                    <p>{{ $non_skripsi->final_score ?? '-' }}</p>
                </div>
                <div>
                    <label class="label-muted">Link Publikasi</label>
                    <p>
                        @if($non_skripsi->publication_url)
                            <a href="{{ $non_skripsi->publication_url }}" target="_blank" class="text-link">{{ $non_skripsi->publication_url }}</a>
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>
            <div class="mt-4">
                <form action="{{ route('mahasiswa.non-skripsi.destroy', $non_skripsi) }}" method="POST" onsubmit="return confirm('Hapus data ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="button button--danger button--inline">Hapus Data</button>
                </form>
            </div>
        </div></article>
    </section>
@endsection
