@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Data Mahasiswa',
        'eyebrow' => 'Admin • CRUD Mahasiswa',
        'description' => 'Kelola data mahasiswa lintas program studi dan angkatan.',
        'actions' => [['href' => route('admin.mahasiswa.create'), 'label' => 'Tambah Mahasiswa']],
        'tableTitle' => 'Daftar Mahasiswa',
        'cols' => '1fr 1fr 0.8fr 0.8fr',
        'columns' => ['Nama Mahasiswa', 'Program Studi', 'Status', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
