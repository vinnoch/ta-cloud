@extends('layouts.app')

@section('content')
    @include('partials.crud.import', [
        'title' => 'Import Mahasiswa',
        'eyebrow' => 'Admin • Import',
        'description' => 'Upload CSV untuk sinkronisasi data mahasiswa.',
        'templateName' => 'template_mahasiswa.csv',
        'submitLabel' => 'Proses Import Mahasiswa',
        'sideCards' => $sideCards,
    ])
@endsection
