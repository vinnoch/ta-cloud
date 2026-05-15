@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Tahun Akademik',
        'eyebrow' => 'Admin • Detail Tahun Akademik',
        'description' => 'Rincian SK, periode aktif, dan keterkaitan dengan template nilai.',
        'actions' => [['href' => route('admin.tahun-akademik.edit', ['id' => $id]), 'label' => 'Edit Tahun Akademik']],
        'cards' => $cards,
        'sideCards' => $sideCards,
    ])
@endsection
