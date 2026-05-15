@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @include('kaprodi.import.partials.import-page', [
        'title' => 'Import Mahasiswa',
        'description' => 'Pilih file CSV untuk pratinjau data sebelum diproses.',
        'templateName' => $templateName,
        'requiredColumns' => $requiredColumns,
        'optionalColumns' => $optionalColumns,
        'inputId' => 'import-mahasiswa-file',
        'labelId' => 'import-mahasiswa-label',
        'backRoute' => route('kaprodi.mahasiswa.index'),
        'storeRoute' => route('kaprodi.import.mahasiswa.store'),
    ])
@endsection
