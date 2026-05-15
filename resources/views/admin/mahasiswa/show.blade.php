@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Mahasiswa',
        'eyebrow' => 'Admin • Detail Mahasiswa',
        'description' => 'Profil mahasiswa, status akademik, dan keterkaitan ke skripsi aktif.',
        'actions' => [['href' => route('admin.mahasiswa.edit', ['id' => $id]), 'label' => 'Edit Mahasiswa']],
        'cards' => $cards,
        'sideCards' => $sideCards,
    ])
@endsection
