@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Tahun Akademik',
        'eyebrow' => 'Admin • CRUD Tahun Akademik',
        'description' => 'Kelola tahun akademik aktif dan histori tahun sebelumnya.',
        'actions' => [['href' => route('admin.tahun-akademik.create'), 'label' => 'Tambah Tahun Akademik']],
        'tableTitle' => 'Daftar Tahun Akademik',
        'cols' => '1fr 1fr 0.8fr 0.8fr',
        'columns' => ['Tahun Akademik', 'Masa Berlaku', 'Status', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
