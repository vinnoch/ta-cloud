@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Program Studi',
        'eyebrow' => 'Admin • Detail Program Studi',
        'description' => 'Ringkasan data program studi beserta penggunaan dan relasi hak akses.',
        'actions' => [['href' => route('admin.program-studi.edit', ['id' => $id]), 'label' => 'Edit Data']],
        'cards' => $cards,
        'sideCards' => $sideCards,
    ])
@endsection
