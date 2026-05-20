@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if (session('warning'))
        <div class="notice notice--danger">{{ session('warning') }}</div>
    @endif

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>Dokumen Final</h2>
                        <p>{{ $skripsi->student->nim ?? '-' }} • {{ $skripsi->periode?->name ?? '-' }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                    </div>
                    <div class="acss-profile-badges">
                        <span class="status-pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if (($templateItems ?? collect())->isEmpty())
        <div class="notice notice--danger">Kaprodi belum mengatur daftar dokumen final yang wajib dikirim melalui sistem.</div>
    @endif

    @if (($documents ?? collect())->isNotEmpty())
        <section class="acss-section-card">
            <div class="acss-section-card__head">
                <div>
                    <h3 class="acss-card-title">Dokumen Terkirim</h3>
                    <p class="acss-muted">Dokumen final yang sudah Anda kirim dan masih dalam proses pengecekan.</p>
                </div>
            </div>
            <div class="acss-section-card__body">
                <div class="table-shell">
                    @foreach (($documents ?? collect()) as $document)
                        @if ($loop->first)
                            <div class="table-shell__head table-shell__grid acss-table-cols-mhs-skripsi-docs">
                                <span>Tanggal</span>
                                <span>Dokumen</span>
                                <span>Versi</span>
                                <span>File PDF</span>
                            </div>
                        @endif
                        <div class="table-shell__row table-shell__grid acss-table-cols-mhs-skripsi-docs">
                            <div class="table-shell__cell">
                                <strong>{{ $document->created_at?->format('d/m/Y') ?? '-' }}</strong>
                                <div class="text-[10px] acss-muted">{{ $document->created_at?->format('H:i') ?? '' }}</div>
                            </div>
                            <div class="table-shell__cell table-shell__cell--title">
                                <strong>Dokumen Final</strong>
                                <div class="acss-muted text-xs">{{ basename($document->file_path) }}</div>
                            </div>
                            <div class="table-shell__cell"><span class="pill">V{{ $document->version_number }}</span></div>
                            <div class="table-shell__cell">
                                <div class="acss-row-actions acss-row-actions--always">
                                    <button type="button" class="text-link acss-action-link" data-preview-open data-preview-url="{{ route('documents.preview', $document) }}" data-preview-title="{{ basename($document->file_path) }}">
                                        @include('partials.icons.eye')
                                        <span>Dokumen</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if (($templateItems ?? collect())->isNotEmpty())
        @if (!($canUpload ?? false))
            <section class="notice notice--info" style="margin-top: 1.5rem;">
                <strong>Pengiriman Dokumen Belum Tersedia</strong>
                <p>{{ $submission['message'] ?? 'Menunggu kelengkapan nilai sidang dari dosen pembimbing dan penguji.' }}</p>
            </section>
        @endif

        <section class="acss-section-card" @if(!($canUpload ?? false)) style="opacity: 0.6; pointer-events: none;" @endif>
            <div class="acss-section-card__head">
                <div>
                    <h3 class="acss-card-title">Form Dokumen Final</h3>
                    <p class="acss-muted">Lengkapi semua dokumen sesuai template periode aktif.</p>
                </div>
            </div>
            <div class="acss-section-card__body">
                <form method="POST" action="{{ route('mahasiswa.skripsi.final.skripsi.store', $skripsi) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="rubric-list">
                        @foreach (($templateItems ?? collect()) as $item)
                            @php
                                $existingSubmission = ($existingSubmissions ?? collect())->get($item->id);
                                $existingFile = $existingSubmission?->documentVersion;
                                $existingLink = $existingSubmission?->notes;
                                $fileInputId = 'dokumen-final-file-' . $item->id;
                            @endphp
                            <article class="rubric-item">
                                <div class="rubric-item__content" style="width:100%;">
                                    <div style="display:grid; grid-template-columns:minmax(0,1fr) minmax(18rem,24rem); gap:1rem; align-items:center;">
                                        <div>
                                            <h4>{{ $item->name }}</h4>
                                            <p class="rubric-item__meta">
                                                {{ $item->is_required ? 'Wajib' : 'Opsional' }}
                                                @if ($existingFile)
                                                    • Sudah upload: {{ basename($existingFile->file_path) }}
                                                @elseif ($existingLink)
                                                    • Sudah isi link
                                                @endif
                                            </p>
                                        </div>
                                        <div class="form-field" style="margin:0;">
                                            @if (($item->type ?? 'file') === 'link')
                                                @if ($existingLink)
                                                    <div class="acss-row-actions acss-row-actions--always">
                                                        <a href="{{ $existingLink }}" target="_blank" rel="noopener noreferrer" class="text-link acss-action-link">
                                                            @include('partials.icons.eye')
                                                            <span>{{ $existingLink }}</span>
                                                        </a>
                                                    </div>
                                                @else
                                                    <input
                                                        type="url"
                                                        name="links[{{ $item->id }}]"
                                                        value="{{ old('links.' . $item->id, $existingLink) }}"
                                                        placeholder="https://..."
                                                        {{ $item->is_required ? 'required' : '' }}
                                                    >
                                                    @error('links.' . $item->id) <small class="field-error">{{ $message }}</small> @enderror
                                                @endif
                                            @else
                                                @if ($existingFile)
                                                    <div class="acss-row-actions acss-row-actions--always">
                                                        <button type="button" class="text-link acss-action-link" data-preview-open data-preview-url="{{ route('documents.preview', $existingFile) }}" data-preview-title="{{ basename($existingFile->file_path) }}">
                                                            @include('partials.icons.eye')
                                                            <span>{{ basename($existingFile->file_path) }}</span>
                                                        </button>
                                                    </div>
                                                @else
                                                    <div style="display:flex; align-items:center; gap:.75rem; flex-wrap:wrap;">
                                                        <input
                                                            id="{{ $fileInputId }}"
                                                            type="file"
                                                            name="files[{{ $item->id }}]"
                                                            accept=".pdf,.doc,.docx"
                                                            class="hidden"
                                                            data-file-input
                                                            data-file-target="dokumen-final-file-name-{{ $item->id }}"
                                                            {{ $item->is_required ? 'required' : '' }}
                                                        >
                                                        <label for="{{ $fileInputId }}" class="button button--muted button--inline" style="margin:0;">Choose File</label>
                                                        <span class="acss-muted text-sm" id="dokumen-final-file-name-{{ $item->id }}">No file chosen</span>
                                                    </div>
                                                    @error('files.' . $item->id) <small class="field-error">{{ $message }}</small> @enderror
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="acss-form-actions" style="margin-top:1rem;">
                        <a class="button button--muted button--inline" href="{{ route('mahasiswa.skripsi.show', $skripsi) }}">Batal</a>
                        <button class="button button--primary button--inline" type="submit" @if(!($canUpload ?? false)) disabled @endif>Kirim Dokumen Final</button>
                    </div>
                </form>
            </div>
        </section>
    @endif

@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-file-input]').forEach(function (input) {
                input.addEventListener('change', function () {
                    const targetId = input.dataset.fileTarget;
                    const target = targetId ? document.getElementById(targetId) : null;
                    if (! target) return;
                    target.textContent = input.files && input.files[0] ? input.files[0].name : 'No file chosen';
                });
            });
        });
    </script>
@endpush
