@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Mahasiswa',
        'eyebrow' => 'Admin • Edit Mahasiswa',
        'description' => 'Perbarui biodata, nim, program studi, dan status mahasiswa.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
