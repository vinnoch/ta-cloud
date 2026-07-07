@extends('layouts.app')

@section('content')
    <section class="acss-section-card">
        <div class="acss-section-card__head">
            <div>
                <h1 class="acss-page-title">{{ $skripsi->title }}</h1>
                <p class="acss-muted">{{ $skripsi->student?->name ?? '-' }} • {{ $skripsi->student?->nim ?? '-' }} • {{ $skripsi->periode?->name ?? '-' }}</p>
            </div>
        </div>

        <div class="acss-section-card__body">
            <div class="grid-two">
                <div>
                    <label class="label-muted">Jenis</label>
                    <p>Non-Skripsi</p>
                </div>
                <div>
                    <label class="label-muted">Artikel Jurnal</label>
                    <p>
                        @php $articleUrl = $skripsi->journal_article_url ?: $record?->publication_url; @endphp
                        @if ($articleUrl)
                            <a href="{{ $articleUrl }}" target="_blank" rel="noopener noreferrer" class="text-link">{{ $articleUrl }}</a>
                        @else
                            -
                        @endif
                    </p>
                </div>
            </div>

            <div class="content-block" style="margin-top: 1rem;">
                <label class="label-muted">Abstrak</label>
                <p>{{ $record?->abstract ?: '-' }}</p>
            </div>

            <div class="grid-two" style="margin-top: 1rem;">
                <div>
                    <label class="label-muted">Ringkasan</label>
                    <p>{{ $record?->summary ?: '-' }}</p>
                </div>
                <div>
                    <label class="label-muted">Nilai Akhir</label>
                    <p>{{ $record?->final_score ?? '-' }}</p>
                </div>
            </div>
        </div>
    </section>
@endsection
