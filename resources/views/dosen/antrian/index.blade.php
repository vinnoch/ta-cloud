@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Antrian Tugas Akhir',
        'eyebrow' => 'Dosen • Queue',
        'description' => 'Daftar skripsi yang ditugaskan kepada dosen sebagai pembimbing atau penguji.',
    ])

    <section class="card">
        @include('partials.tables.data-table', [
            'cols' => '1.5fr 1fr 0.9fr 0.9fr',
            'columns' => ['Mahasiswa / Judul', 'Peran', 'Status', 'Aksi'],
            'rows' => $rows,
        ])
    </section>
@endsection
