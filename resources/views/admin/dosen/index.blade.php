@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Data Dosen',
        'eyebrow' => 'Admin • CRUD Dosen',
        'description' => 'Kelola data dosen lintas program studi.',
        'actions' => [['href' => route('admin.dosen.create'), 'label' => 'Tambah Dosen']],
        'tableTitle' => 'Daftar Dosen',
        'cols' => '1fr 1fr 0.8fr 0.8fr',
        'columns' => ['Nama Dosen', 'Program Studi', 'Status', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
