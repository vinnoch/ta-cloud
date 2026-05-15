@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Periode',
        'eyebrow' => 'Admin • Detail Periode',
        'description' => 'Rincian kode periode, tahun akademik, dan penggunaan pada sidang/template nilai.',
        'actions' => [['href' => route('admin.periode.edit', ['id' => $id]), 'label' => 'Edit Periode']],
        'cards' => $cards,
        'sideCards' => $sideCards,
    ])
@endsection
