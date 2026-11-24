@extends('layouts.app')

@section('content')
    <section class="card">
        <div class="section-heading">
            <div><h1 class="acss-page-title">Antrian Tugas Akhir</h1></div>
        </div>
        @include('partials.tables.data-table', [
            'cols' => '1.5fr 1fr 0.9fr 0.9fr',
            'columns' => ['Mahasiswa / Judul', 'Peran', 'Status', 'Aksi'],
            'rows' => $rows,
        ])
    </section>
@endsection
