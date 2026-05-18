@extends('layouts.app')

@section('content')
    @include('partials.page-header', [
        'title' => $submission['title'],
        'eyebrow' => 'Mahasiswa • Final Phase',
        'description' => 'Form ini aktif setelah seluruh nilai untuk tahap terkait tersedia.',
    ])

    <section class="panel-grid">
        <article class="card">
            <div class="card-list">
                @foreach ($checklist as $item)
                    <article class="list-card">
                        <div>
                            <h4>{{ $item['title'] }}</h4>
                            <p>{{ $item['description'] }}</p>
                        </div>
                        <div class="status-stack">
                            @if (($item['type'] ?? 'status') === 'button')
                                <a class="button button--inline" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                            @elseif (($item['type'] ?? 'status') === 'link')
                                <a class="text-link" href="{{ $item['href'] }}">{{ $item['label'] }}</a>
                            @else
                                <span class="pill">{{ $item['status'] }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <form method="POST" action="{{ route('mahasiswa.final.submit', [$skripsi, $submission['event']]) }}" enctype="multipart/form-data" style="margin-top: 1rem;">
                @csrf
                <div class="form-grid">
                    <label class="form-field">
                        <span>Dokumen Final</span>
                        <input type="file" name="file" accept=".pdf,.doc,.docx" required>
                        <small class="acss-muted">Format PDF, DOC, atau DOCX. Maksimum 20 MB.</small>
                        @error('file') <small class="field-error">{{ $message }}</small> @enderror
                    </label>

                    <label class="form-field">
                        <span>Catatan Tambahan (opsional)</span>
                        <textarea name="notes" rows="4" placeholder="Tambahkan keterangan revisi atau catatan singkat...">{{ old('notes') }}</textarea>
                        @error('notes') <small class="field-error">{{ $message }}</small> @enderror
                    </label>

                    @if ($submission['show_journal_field'])
                        <label class="form-field">
                            <span>Link Artikel Jurnal (opsional)</span>
                            <input type="url" name="journal_article_url" value="{{ old('journal_article_url', $skripsi->journal_article_url) }}" placeholder="https://...">
                            @error('journal_article_url') <small class="field-error">{{ $message }}</small> @enderror
                        </label>
                    @endif
                </div>

                <div class="acss-form-actions " style="justify-content:flex-end;">
                    
                    <button class="button button--inline" type="submit">Kirim Final Submission</button>
                </div>
            </form>
        </article>

        <aside class="stack-list">
            @foreach ($cards as $card)
                @include('partials.cards.info', $card)
            @endforeach
        </aside>
    </section>
@endsection
