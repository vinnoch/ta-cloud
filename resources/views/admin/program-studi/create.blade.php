@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Program Studi',
        'eyebrow' => 'Admin • Create Program Studi',
        'description' => 'Form frontend untuk menambahkan program studi baru.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Program Studi',
        'sideCards' => $sideCards,
    ])
@endsection
