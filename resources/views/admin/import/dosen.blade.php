@extends('layouts.app')

@section('content')
    @include('partials.crud.import', [
        'title' => 'Import Dosen',
        'eyebrow' => 'Admin • Import',
        'description' => 'Upload CSV untuk sinkronisasi data dosen.',
        'templateName' => 'template_dosen.csv',
        'submitLabel' => 'Proses Import Dosen',
        'sideCards' => $sideCards,
    ])
@endsection
