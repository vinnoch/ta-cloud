@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @include('kaprodi.import.partials.import-page', [
        'title' => 'Import Dosen',
        'description' => 'Pilih file CSV untuk pratinjau data sebelum diproses.',
        'templateName' => $templateName,
        'requiredColumns' => $requiredColumns,
        'optionalColumns' => $optionalColumns,
        'inputId' => 'import-dosen-file',
        'labelId' => 'import-dosen-label',
        'backRoute' => route('kaprodi.dosen.index'),
        'storeRoute' => route('kaprodi.import.dosen.store'),
    ])
@endsection
