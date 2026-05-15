@extends('layouts.app')

@section('content')
    <section class="acss-stat-grid">
        @foreach(($cards ?? []) as $card)
            <article class="acss-stat-card">
                <span class="acss-stat-label">{{ $card['eyebrow'] ?? '' }}</span>
                <strong class="acss-stat-value">{{ $card['title'] ?? '-' }}</strong>
                <small class="acss-stat-hint">{{ $card['description'] ?? '' }}</small>
            </article>
        @endforeach
    </section>

    <section class="acss-section-card mt-4">
        <div class="acss-section-card__body">
            <span class="acss-stat-label">{{ $decisionCard['eyebrow'] ?? '' }}</span>
            <h3 class="acss-card-title mt-2">{{ $decisionCard['title'] ?? '-' }}</h3>
            <p class="acss-muted mt-2">{{ $decisionCard['description'] ?? '' }}</p>
        </div>
    </section>
@endsection
