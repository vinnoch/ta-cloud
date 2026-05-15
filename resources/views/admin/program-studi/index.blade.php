@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Program Studi',
        'eyebrow' => 'Admin • CRUD Program Studi',
        'description' => 'Master data program studi untuk mendukung skenario multi program studi.',
        'actions' => [['href' => route('admin.program-studi.create'), 'label' => 'Tambah Program Studi']],
        'tableTitle' => 'Daftar Program Studi',
        'cols' => '1fr 1fr 0.8fr',
        'columns' => ['Nama Program Studi', 'Jenjang', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
