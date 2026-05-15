@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Tahun Akademik',
        'eyebrow' => 'Admin • Edit Tahun Akademik',
        'description' => 'Perbarui status aktif, tanggal berlaku, dan metadata tahun akademik.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
