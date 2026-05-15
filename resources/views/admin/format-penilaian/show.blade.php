@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Format Nilai',
        'eyebrow' => 'Admin • Detail Format',
        'description' => 'Rincian bobot, kategori, dan status penggunaan format nilai.',
        'actions' => [['href' => route('admin.format-penilaian.edit', ['id' => $id]), 'label' => 'Edit Format']],
        'cards' => $cards,
        'sideCards' => $sideCards,
        'timeline' => $timeline ?? [],
        'timelineTitle' => 'Riwayat Publikasi Format',
    ])
@endsection
