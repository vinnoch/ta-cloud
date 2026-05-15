@extends('layouts.app')

@section('content')
    @include('partials.crud.form', [
        'title' => 'Edit Format Nilai',
        'eyebrow' => 'Admin • Edit Format',
        'description' => 'Perbarui bobot komponen, rubric, dan penempatan periode format nilai.',
        'fields' => $fields,
        'submitLabel' => 'Simpan Perubahan',
        'sideCards' => $sideCards,
    ])
@endsection
