@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Dosen',
        'eyebrow' => 'Admin • Detail Dosen',
        'description' => 'Profil dosen, penugasan akademik, dan hak akses terkait.',
        'actions' => [['href' => route('admin.dosen.edit', ['id' => $id]), 'label' => 'Edit Dosen']],
        'cards' => $cards,
        'sideCards' => $sideCards,
        'timeline' => $timeline ?? [],
        'timelineTitle' => 'Aktivitas Akademik',
    ])
@endsection
