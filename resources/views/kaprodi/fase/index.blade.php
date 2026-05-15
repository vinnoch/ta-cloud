@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Phase Control Board',
        'eyebrow' => 'Kaprodi • Workflow',
        'description' => 'Board kontrol fase untuk mengelola perpindahan Proposal, Bimbingan, Pasca Sidang, dan Final.',
    ])

    <section class="four-column">
        @foreach ($phases as $phase)
            @include('partials.cards.info', $phase)
        @endforeach
    </section>
@endsection
