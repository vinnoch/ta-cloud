@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Program Studi',
        'eyebrow' => 'Admin • Edit Program Studi',
        'description' => 'Perbarui nama, kode, jenjang, dan status aktif program studi.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
