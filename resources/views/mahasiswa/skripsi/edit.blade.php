@extends('layouts.app')

@section('content')
    @php
        $taType = $skripsi->type;
        $taLabel = $taType === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi';
        $taRoutePrefix = $taType === 'non_skripsi' ? 'mahasiswa.non-skripsi' : 'mahasiswa.skripsi';
    @endphp
    @include('partials.page-header', [
        'title' => 'Edit '.$taLabel,
        'eyebrow' => 'Mahasiswa • Edit',
        'description' => 'Perbarui judul dan tipe tugas akhir.',
    ])

    <section class="panel-grid">
        <article class="acss-crud-card">
            <form class="acss-crud-body form-grid" method="POST" action="{{ route($taRoutePrefix.'.update', $skripsi) }}">
                @csrf
                @method('PUT')
                <label class="form-field">
                    <span>Judul Penelitian</span>
                    <input type="text" name="title" value="{{ old('title', $skripsi->title) }}" required>
                </label>
                <label class="form-field">
                    <span>Tipe</span>
                    <input type="hidden" name="type" value="{{ $taType }}">
                    <input type="text" value="{{ $taLabel }}" readonly aria-readonly="true">
                </label>
                <div class="form-actions">
                    <button class="button" type="submit">Simpan Perubahan</button>
                    <a class="button button--muted" href="{{ route($taRoutePrefix.'.show', $skripsi) }}">Batal</a>
                </div>
            </form>
        </article>
    </section>
@endsection
