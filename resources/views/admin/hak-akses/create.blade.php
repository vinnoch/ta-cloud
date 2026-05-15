@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Hak Akses',
        'eyebrow' => 'Admin • Create Capability',
        'description' => 'Tambahkan rule capability baru untuk role dan program studi tertentu.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Hak Akses',
        'sideCards' => $sideCards,
    ])
@endsection
