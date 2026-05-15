@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Periode',
        'eyebrow' => 'Admin • Create Periode',
        'description' => 'Tambah periode baru seperti 20261 atau 20262.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Periode',
        'sideCards' => $sideCards,
    ])
@endsection
