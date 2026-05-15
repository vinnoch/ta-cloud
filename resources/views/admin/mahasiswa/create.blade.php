@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Mahasiswa',
        'eyebrow' => 'Admin • Create Mahasiswa',
        'description' => 'Form frontend untuk menambahkan mahasiswa baru.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Mahasiswa',
        'sideCards' => $sideCards,
    ])
@endsection
