@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => 'Review Dokumen',
        'eyebrow' => 'Dosen • Review',
        'description' => 'Panel review untuk meninjau dokumen terbaru, memberi catatan revisi, dan menandai kelayakan lanjut fase.',
    ])

    <section class="panel-grid">
        <article class="card">
            <div class="form-grid">
                @include('partials.forms.field', ['label' => 'Versi yang Direview', 'value' => 'v4 Proposal Revisi'])
                @include('partials.forms.field', ['label' => 'Catatan Reviewer', 'type' => 'textarea', 'placeholder' => 'Masukkan catatan revisi atau persetujuan...'])
                <div class="pill-row">
                    <span class="pill">Approve</span>
                    <span class="pill">Request Revision</span>
                    <span class="pill">Butuh Diskusi</span>
                </div>
            </div>
        </article>

        <aside class="stack-list">
            @foreach ($cards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </aside>
    </section>
@endsection
