@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Dosen',
        'eyebrow' => 'Admin • Create Dosen',
        'description' => 'Form frontend untuk menambahkan dosen baru.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Dosen',
        'sideCards' => $sideCards,
    ])
@endsection
