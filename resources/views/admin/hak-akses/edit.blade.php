@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Hak Akses',
        'eyebrow' => 'Admin • Edit Capability',
        'description' => 'Perbarui capability matrix untuk role dan program studi target.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
