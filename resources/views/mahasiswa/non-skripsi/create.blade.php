@extends('layouts.app')
@section('content')
    @include('partials.page-header', ['title' => 'Input Data Non-Skripsi', 'eyebrow' => 'Mahasiswa • Non-Skripsi'])
    <section class="card">
        <form method="POST" action="{{ route('mahasiswa.non-skripsi.store') }}" class="form-grid">
            @csrf
            <label class="form-field"><span>Judul / Topik</span><input type="text" name="title" required></label>
            <label class="form-field"><span>Abstrak</span><textarea name="abstract" rows="4" required></textarea></label>
            <div class="form-actions"><button class="button" type="submit">Simpan</button></div>
        </form>
    </section>
@endsection
