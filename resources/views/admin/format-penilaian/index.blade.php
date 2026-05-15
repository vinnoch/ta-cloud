@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Format Nilai',
        'eyebrow' => 'Admin • CRUD Format Nilai',
        'description' => 'Kelola format penilaian lintas program studi dan periode akademik.',
        'actions' => [['href' => route('admin.format-penilaian.create'), 'label' => 'Tambah Format Nilai']],
        'tableTitle' => 'Daftar Format Nilai',
        'cols' => '1fr 0.8fr 0.8fr 0.8fr',
        'columns' => ['Format', 'Periode', 'Status', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
