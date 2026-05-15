@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>Histori Bimbingan</h2>
                        <p>{{ $skripsi->student->name ?? '-' }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                    </div>
                    <span class="status-pill">LOGBOOK</span>
                </div>
            </div>
        </div>
    </section>

    <section class="acss-crud-card mt-4">
        <div class="acss-crud-head acss-crud-head--inline">
            <div><h3 class="acss-card-title">Riwayat Pertemuan</h3></div>
            <div class="acss-crud-head__actions">
                <form method="GET" action="{{ route('mahasiswa.skripsi.bimbingan.index', $skripsi) }}" class="acss-inline-filter-form">
                    <label class="field field--inline">
                        <select name="reviewer_id" class="input acss-filter-select-bordered" onchange="this.form.submit()">
                            <option value="0">Semua Dosen</option>
                            @foreach (($reviewerOptions ?? []) as $reviewer)
                                <option value="{{ $reviewer->id }}" @selected((int) ($selectedReviewerId ?? 0) === (int) $reviewer->id)>{{ $reviewer->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </form>
                <div class="acss-export-actions">
                    <a href="{{ route('mahasiswa.skripsi.bimbingan.export.pdf', ['skripsi' => $skripsi->id, 'reviewer_id' => (int) ($selectedReviewerId ?? 0)]) }}" class="acss-export-link acss-export-link--pdf" target="_blank" aria-label="Download PDF">
                        <span class="acss-export-doc-icon">
                            @include('partials.icons.file-plain')
                            <span class="acss-export-doc-badge acss-export-doc-badge--pdf">PDF</span>
                            <span class="acss-export-doc-arrow">@include('partials.icons.download-arrow')</span>
                        </span>
                    </a>
                    <a href="{{ route('mahasiswa.skripsi.bimbingan.export.csv', ['skripsi' => $skripsi->id, 'reviewer_id' => (int) ($selectedReviewerId ?? 0)]) }}" class="acss-export-link acss-export-link--csv" aria-label="Download CSV">
                        <span class="acss-export-doc-icon">
                            @include('partials.icons.file-plain')
                            <span class="acss-export-doc-badge acss-export-doc-badge--csv">CSV</span>
                            <span class="acss-export-doc-arrow">@include('partials.icons.download-arrow')</span>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        <div class="acss-crud-body">
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid acss-table-cols-mhs-bimbingan-log">
                    <span>Tanggal</span>
                    <span>Dosen</span>
                    <span>Catatan</span>
                    <span>Dokumen</span>
                </div>
                @forelse ($meetings as $meeting)
                    <div id="bimbingan-{{ $meeting['record']->id }}" class="table-shell__row table-shell__grid acss-table-cols-mhs-bimbingan-log acss-hover-row-group" title="Maksimum 2 MB. Format file: DOC, DOCX, atau PDF.">
                        <div class="table-shell__cell">
                            <strong>{{ $meeting['date'] }}</strong>
                        </div>
                        <div class="table-shell__cell">{{ $meeting['reviewer'] ?? '-' }}</div>
                        <div class="table-shell__cell">{{ $meeting['summary'] }}</div>
                        <div class="table-shell__cell acss-upload-cell-hint">
                            <div class="acss-revision-widget" data-upload-widget>
                                @if ($meeting['has_revision'])
                                    <div class="acss-revision-widget__done">
                                        <button class="text-link acss-action-link acss-preview-link-inline" type="button" data-preview-open data-preview-url="{{ $meeting['revision_url'] }}" data-preview-title="{{ $meeting['revision_name'] }}" title="{{ $meeting['revision_name'] }}">@include('partials.icons.file')<span>Dokumen</span></button>
                                        <div class="acss-row-actions">
                                            <form method="POST" action="{{ $meeting['upload_url'] }}" enctype="multipart/form-data" data-instant-upload-form class="acss-inline-form">
                                                @csrf
                                                @method('PUT')
                                                <input class="acss-file-input-hidden" id="bimbingan_replace_{{ $loop->index }}" type="file" name="revision_file" accept=".pdf,.doc,.docx" required data-auto-upload>
                                                <label class="text-link acss-action-link" for="bimbingan_replace_{{ $loop->index }}">@include('partials.icons.edit')<span>Ganti</span></label>
                                            </form>
                                            <button class="acss-action-link acss-action-link--danger" type="button" data-remove-revision data-remove-url="{{ $meeting['remove_url'] }}">@include('partials.icons.trash')<span>Hapus</span></button>
                                        </div>
                                    </div>
                                @else
                                    <form method="POST" action="{{ $meeting['upload_url'] }}" enctype="multipart/form-data" data-instant-upload-form data-initial-state>
                                        @csrf
                                        @method('PUT')
                                        <div class="acss-inline-file-row" style="gap: 0.35rem;">
                                            <label class="button button--muted button--inline acss-btn-sm acss-file-trigger" for="bimbingan_rev_{{ $loop->index }}">Pilih File</label>
                                            <span class="acss-file-name" data-file-label="bimbingan_rev_{{ $loop->index }}" style="font-size: 0.75rem; max-width: 8rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Tidak ada file</span>
                                            <input class="acss-file-input-hidden" id="bimbingan_rev_{{ $loop->index }}" type="file" name="revision_file" accept=".pdf,.doc,.docx" required data-auto-upload>
                                        </div>
                                        <small class="acss-upload-cell-hint__text">Maksimum 2 MB. Format file: DOC, DOCX, atau PDF.</small>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada histori bimbingan.</div>
                @endforelse
            </div>
        </div>
    </section>


    <div class="acss-modal" data-pdf-preview-modal hidden>
        <div class="acss-modal__backdrop" data-pdf-preview-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--pdf">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Preview Dokumen</h3>
                    <p class="acss-muted mt-1" data-pdf-preview-name>-</p>
                </div>
                <button type="button" class="acss-modal__close" data-pdf-preview-close aria-label="Tutup">×</button>
            </div>
            <div class="acss-pdf-preview">
                <iframe data-pdf-preview-frame title="Preview Dokumen PDF"></iframe>
            </div>
        </div>
    </div>

@endsection

@include('mahasiswa.bimbingan.partials.revision-upload-script')
