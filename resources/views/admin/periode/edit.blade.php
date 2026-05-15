@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Periode',
        'eyebrow' => 'Admin • Edit Periode',
        'description' => 'Perbarui status aktif dan relasi tahun akademik.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
