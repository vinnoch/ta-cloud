@extends('layouts.app')

@section('content')
    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">{{ $skripsi->title }}</h1>
                <p class="acss-muted">{{ $skripsi->student?->name ?? '-' }} • {{ $skripsi->student?->level?->name ?? 'Sistem Informasi' }}</p>
            </div>
        </div>
        <div class="acss-crud-body">
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid" style="--table-cols:1.2fr .9fr .9fr">
                    <span>Dokumen</span>
                    <span>Preview</span>
                    <span>Download</span>
                </div>
                @forelse (($libraryDocuments ?? collect()) as $submission)
                    @php
                        $document = $submission->documentVersion;
                        $item = $submission->templateItem;
                        $isLink = ($item->type ?? 'file') === 'link';
                    @endphp
                    <div class="table-shell__row table-shell__grid" style="--table-cols:1.2fr .9fr .9fr">
                        <div class="table-shell__cell">
                            <strong>{{ $item?->name ?? 'Dokumen Final' }}</strong>
                            <small>
                                {{ $isLink
                                    ? trim((string) $submission->notes) ?: '-'
                                    : ($document?->file_path ? basename($document->file_path) : '-') }}
                            </small>
                        </div>
                        <div class="table-shell__cell">
                            @if ($isLink && filled($submission->notes))
                                <a class="text-link acss-action-link" href="{{ $submission->notes }}" target="_blank" rel="noopener">Buka Link</a>
                            @elseif ($document)
                                <a class="text-link acss-action-link" href="{{ route('documents.preview', $document) }}" target="_blank">Preview PDF</a>
                            @else
                                -
                            @endif
                        </div>
                        <div class="table-shell__cell">
                            @if ($isLink && filled($submission->notes))
                                <a class="text-link acss-action-link" href="{{ $submission->notes }}" target="_blank" rel="noopener">Kunjungi</a>
                            @elseif ($document)
                                <a class="text-link acss-action-link" href="{{ route('documents.download', $document) }}">Download PDF</a>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                @empty
                    @if ($finalDocument)
                        <div class="table-shell__row table-shell__grid" style="--table-cols:1.2fr .9fr .9fr">
                            <div class="table-shell__cell">
                                <strong>{{ basename($finalDocument->file_path) }}</strong>
                            </div>
                            <div class="table-shell__cell">
                                <a class="text-link acss-action-link" href="{{ route('documents.preview', $finalDocument) }}" target="_blank">Preview PDF</a>
                            </div>
                            <div class="table-shell__cell">
                                <a class="text-link acss-action-link" href="{{ route('documents.download', $finalDocument) }}">Download PDF</a>
                            </div>
                        </div>
                    @endif
                @endforelse
            </div>
        </div>
    </section>
@endsection
