@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Edit Skripsi',
        'eyebrow' => 'Mahasiswa • Edit',
        'description' => 'Perbarui judul, tipe, link artikel jurnal.',
    ])

    <section class="panel-grid">
        <article class="card">
            <form class="form-grid" method="POST" action="{{ route('mahasiswa.skripsi.update', $skripsi) }}">
                @csrf
                @method('PUT')
                <label class="form-field">
                    <span>Judul Penelitian</span>
                    <input type="text" name="title" value="{{ old('title', $skripsi->title) }}" required>
                </label>
                <label class="form-field">
                    <span>Tipe</span>
                    <select name="type" required>
                        <option value="skripsi" @selected(old('type', $skripsi->type) === 'skripsi')>Skripsi</option>
                        <option value="non_skripsi" @selected(old('type', $skripsi->type) === 'non_skripsi')>Non Skripsi</option>
                    </select>
                </label>
                <label class="form-field">
                    <span>Link Artikel Jurnal (opsional)</span>
                    <input type="url" name="journal_article_url" value="{{ old('journal_article_url', $skripsi->journal_article_url) }}">
                </label>
                <div class="form-actions">
                    <button class="button" type="submit">Simpan Perubahan</button>
                    <a class="button button--muted" href="{{ route('mahasiswa.skripsi.show', $skripsi) }}">Batal</a>
                </div>
            </form>
        </article>
    </section>
@endsection
