@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Periode Akademik',
        'eyebrow' => 'Admin • CRUD Periode',
        'description' => 'Periode dikelola sebagai entitas terpisah dari Tahun Akademik.',
        'actions' => [['href' => route('admin.periode.create'), 'label' => 'Tambah Periode']],
        'tableTitle' => 'Daftar Periode',
        'cols' => '0.8fr 1fr 0.8fr 0.8fr',
        'columns' => ['Kode', 'Tahun Akademik', 'Status', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
