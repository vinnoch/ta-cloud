@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Format Nilai',
        'eyebrow' => 'Admin • Create Format',
        'description' => 'Buat format penilaian baru beserta bobot komponen dan konteks periodenya.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Format Nilai',
        'sideCards' => $sideCards,
    ])
@endsection
