@extends('layouts.app')

@section('content')
    @include('partials.crud.show', [
        'title' => 'Detail Hak Akses',
        'eyebrow' => 'Admin • Detail Capability',
        'description' => 'Rincian capability matrix untuk role dan program studi tertentu.',
        'cards' => $cards,
        'sideCards' => $sideCards,
    ])
@endsection
