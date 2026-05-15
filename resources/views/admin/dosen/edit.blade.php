@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Dosen',
        'eyebrow' => 'Admin • Edit Dosen',
        'description' => 'Perbarui biodata, program studi, dan status dosen.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
