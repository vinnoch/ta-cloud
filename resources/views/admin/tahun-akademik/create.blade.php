@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Tambah Tahun Akademik',
        'eyebrow' => 'Admin • Create Tahun Akademik',
        'description' => 'Buat tahun akademik baru beserta SK dan rentang berlakunya.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Tahun Akademik',
        'sideCards' => $sideCards,
    ])
@endsection
