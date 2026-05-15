@extends('layouts.app')

@section('content')
    @include('partials.crud.index', [
        'title' => 'Hak Akses',
        'eyebrow' => 'Admin • Capability Matrix',
        'description' => 'Pengaturan hak akses per role dan program studi, termasuk hak modifikasi Dosen oleh Kaprodi non Sistem Informasi.',
        'tableTitle' => 'Daftar Konfigurasi Hak Akses',
        'cols' => '1fr 1fr 1fr 0.8fr',
        'columns' => ['Role Target', 'Program Studi', 'Capability', 'Aksi'],
        'rows' => $rows,
        'sideCards' => $sideCards,
    ])
@endsection
