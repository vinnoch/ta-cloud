@extends('layouts.app')
@section('content')
    @include('partials.page-header', ['title' => 'Edit Data Non-Skripsi', 'eyebrow' => 'Mahasiswa • Non-Skripsi'])
    <section class="card">
        <form method="POST" action="{{ route('mahasiswa.non-skripsi.update', $non_skripsi) }}" class="form-grid">
            @csrf @method('PUT')
            <label class="form-field"><span>Judul / Topik</span><input type="text" name="title" value="{{ $non_skripsi->summary }}" required></label>
            <label class="form-field"><span>Abstrak</span><textarea name="abstract" rows="4" required>{{ $non_skripsi->abstract }}</textarea></label>
            <div class="form-actions"><button class="button" type="submit">Update</button></div>
        </form>
    </section>
@endsection
