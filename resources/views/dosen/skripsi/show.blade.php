@extends('layouts.app')

@section('content')
    @if(session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="notice notice--warning">{{ session('info') }}</div>
    @endif

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student?->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $skripsi->student?->name ?? '-' }}</h2>
                        <p>{{ $skripsi->student?->nim ?? '-' }} • {{ $skripsi->periode?->name ?? '-' }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                    </div>
                    <span class="status-pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                </div>
                <div class="form-actions form-actions--inline mt-4">
                    @if(in_array($skripsi->current_phase, ['sidang_skripsi', 'revisi_sidang_skripsi']))
                        <a class="button button--inline" href="{{ route('dosen.penilaian.show', $skripsi) }}"><span class="dosen-btn-icon">@include("partials.icons.clipboard")</span><span>Isi Nilai</span></a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @include('partials.skripsi-phase-timeline', ['skripsiTimelineRecord' => $skripsi, 'timelineTitle' => 'Timeline Fase Skripsi'])

    <div class="acss-stack-sections mt-4">
        
        <section class="card mb-4">
            <div class="section-heading"><div><h3>Dokumen Utama</h3></div></div>
            <div class="table-shell">
                    <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi-docs">
                        <span>Tanggal</span>
                        <span>Dokumen</span>
                        <span>Versi</span>
                    </div>
                    @forelse($skripsi->documentVersions->sortByDesc('created_at') as $doc)
                        @php
                            $phaseLabel = str($doc->phase)->replace(['_', '-'], ' ')->title()->toString();
                            $fileName = basename((string) $doc->file_path);
                        @endphp
                        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-skripsi-docs acss-hover-row-group">
                            <div class="table-shell__cell">
                                <strong>{{ $doc->created_at?->format('d/m/Y') ?? '-' }}</strong>
                                <div class="text-[10px] acss-muted">{{ $doc->created_at?->format('H:i') ?? '' }}</div>
                            </div>
                            <div class="table-shell__cell table-shell__cell--title">
                                <strong>{{ $phaseLabel }}</strong>
                                <div class="acss-muted text-xs mt-1">{{ \Illuminate\Support\Str::limit($fileName, 30) }}</div>
                                <div class="acss-row-actions">
                                    <button type="button" class="text-link acss-action-link" data-preview-open data-preview-url="{{ \Illuminate\Support\Facades\Storage::url($doc->file_path) }}" data-preview-title="{{ $fileName }}">@include('partials.icons.eye')<span>File PDF</span></button>
                                </div>
                            </div>
                            <div class="table-shell__cell"><span class="pill">V{{ (int) ($doc->version_number ?: 1) }}</span></div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada dokumen utama.</div>
                    @endforelse
                </div>
        </section>

        <section class="card">
            <div class="section-heading acss-crud-head--inline">
                <div>
                    <h3>Histori Bimbingan</h3>
                </div>
                @if(in_array($myRoleType ?? null, ['pembimbing_1','pembimbing_2']) && $skripsi->current_phase === 'bimbingan_skripsi')
                    <div class="acss-crud-head__actions">
                        <button type="button" class="button button--inline" data-bimbingan-create-modal-open><span class="dosen-btn-icon">@include("partials.icons.plus")</span><span>Tambah Bimbingan</span></button>
                    </div>
                @endif
            </div>
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi-bimb">
                        <span>Tanggal</span>
                        <span>Fase</span>
                        <span>Catatan</span>
                        <span>File PDF</span>
                    </div>
                    @forelse($skripsi->bimbingans->sortByDesc('meeting_date') as $item)
                        <div class="table-shell__row table-shell__grid acss-table-cols-dosen-skripsi-bimb acss-hover-row-group">
                            <div class="table-shell__cell">
                                <strong>{{ $item->meeting_date?->format('d/m/Y') ?? '-' }}</strong><div class='text-[10px] acss-muted'>{{ $item->created_at?->format('H:i') ?? '-' }}</div>
                                @if((int) $item->reviewer_id === (int) auth()->id())
                                    <div class="acss-row-actions">
                                        <button
                                            type="button"
                                            class="text-link acss-action-link"
                                            data-bimbingan-edit-modal-open
                                            data-update-url="{{ route('dosen.bimbingan.update', $item) }}"
                                            data-meeting-date="{{ optional($item->meeting_date)->format('Y-m-d') }}"
                                            data-lecturer-notes="{{ e($item->lecturer_notes ?? '') }}"
                                        >
                                            @include('partials.icons.edit')<span>Edit</span>
                                        </button>
                                    </div>
                                @endif
                            </div>
                            <div class="table-shell__cell"><span class="pill">{{ str($item->phase)->replace('_',' ')->title() }}</span></div>
                            <div class="table-shell__cell">{{ \Illuminate\Support\Str::limit($item->lecturer_notes ?: '-', 90) }}</div>
                            <div class="table-shell__cell">
                                @if ($item->has_revision_file)
                                    <div class="acss-row-actions acss-row-actions--always">
                                        <button type="button" class="text-link acss-action-link" data-preview-open data-preview-url="{{ $item->revision_file_url }}" data-preview-title="{{ $item->reviewedVersion?->file_path ? basename($item->reviewedVersion->file_path) : 'Dokumen Revisi' }}">@include('partials.icons.eye')<span>File PDF</span></button>
                                    </div>
                                @else
                                    <span class="acss-muted text-xs italic">Mahasiswa belum submit</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada bimbingan.</div>
                    @endforelse
                </div>
            </div>
        </section>

        @if(($myRoleType ?? null) === 'pembimbing_1' && $skripsi->current_phase === 'bimbingan_skripsi')
            <div class="form-actions form-actions--inline mt-4">
                <button type="button" class="button button--inline button--success" data-sidang-create-modal-open><span class="dosen-btn-icon">@include("partials.icons.send")</span><span>Ajukan Permohonan Sidang</span></button>
            </div>
        @endif

        @if ($skripsi->current_phase === 'review_dokumen_final')
            <section class="acss-section-card">
                <div class="acss-section-card__head"><div><h3 class="acss-card-title">Status Approval Dokumen Final</h3></div></div>
                <div class="acss-section-card__body">
                    <div class="table-shell">
                        <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi-approval">
                            <span>Reviewer</span>
                            <span>Peran</span>
                            <span>Status</span>
                        </div>
                        @forelse($finalApprovals as $approval)
                            <div class="table-shell__row table-shell__grid acss-table-cols-dosen-skripsi-approval acss-hover-row-group">
                                <div class="table-shell__cell"><strong>{{ $approval->reviewer?->name ?? '-' }}</strong></div>
                                <div class="table-shell__cell">{{ str($approval->role_type)->replace('_', ' ')->title() }}</div>
                                <div class="table-shell__cell"><span class="pill">{{ strtoupper($approval->status) }}</span></div>
                            </div>
                        @empty
                            <div class="empty-state">Belum ada status approval final.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        @endif
    </div>

    <div class="acss-modal" data-bimbingan-create-modal hidden>
        <div class="acss-modal__backdrop" data-bimbingan-create-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambah Bimbingan</h3>
                </div>
                <button type="button" class="acss-modal__close" data-bimbingan-create-modal-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('dosen.bimbingan.store', $skripsi) }}" class="acss-form-stack-tight">
                @csrf
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Tanggal</span>
                        <input type="date" name="meeting_date" value="{{ old('meeting_date', now()->format('Y-m-d')) }}" required>
                    </label>
                    <label class="form-field">
                        <span>Catatan Dosen</span>
                        <textarea name="lecturer_notes" rows="5" placeholder="Tambahkan catatan bimbingan untuk mahasiswa."></textarea>
                    </label>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-bimbingan-create-modal-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Bimbingan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acss-modal" data-sidang-create-modal hidden>
        <div class="acss-modal__backdrop" data-sidang-create-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Ajukan Permohonan Sidang</h3>
                </div>
                <button type="button" class="acss-modal__close" data-sidang-create-modal-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ route('dosen.sidang-request.store', $skripsi) }}" class="acss-form-stack-tight">
                @csrf
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Catatan Permohonan</span>
                        <textarea name="note" rows="5" placeholder="Tambahkan catatan kesiapan sidang jika perlu."></textarea>
                    </label>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-sidang-create-modal-close>Batal</button>
                    <button class="button button--inline button--success" type="submit">Kirim Permohonan Sidang</button>
                </div>
            </form>
        </div>
    </div>

    <div class="acss-modal" data-bimbingan-edit-modal hidden>
        <div class="acss-modal__backdrop" data-bimbingan-edit-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Bimbingan</h3>
                </div>
                <button type="button" class="acss-modal__close" data-bimbingan-edit-modal-close aria-label="Tutup">×</button>
            </div>
            <form id="bimbingan-edit-form" method="POST" action="#" class="acss-form-stack-tight">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Tanggal</span>
                        <input id="bimbingan-edit-meeting-date" type="date" name="meeting_date" required>
                    </label>
                    <label class="form-field">
                        <span>Catatan Dosen</span>
                        <textarea id="bimbingan-edit-lecturer-notes" name="lecturer_notes" rows="5"></textarea>
                    </label>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-bimbingan-edit-modal-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    @include('partials.pdf-viewer-modal')

    <script>
        (() => {
            const bimbinganEditModal = document.querySelector('[data-bimbingan-edit-modal]');
            const bimbinganCreateModal = document.querySelector('[data-bimbingan-create-modal]');
            const sidangCreateModal = document.querySelector('[data-sidang-create-modal]');
            
            const bimbinganEditForm = document.getElementById('bimbingan-edit-form');
            const bimbinganEditDate = document.getElementById('bimbingan-edit-meeting-date');
            const bimbinganEditNotes = document.getElementById('bimbingan-edit-lecturer-notes');

            const pdfModal = document.querySelector('[data-pdf-preview-modal]');
            const pdfFrame = document.querySelector('[data-pdf-preview-frame]');
            const pdfName = document.querySelector('[data-pdf-preview-name]');

            const toggleModal = (modal, show) => {
                if (!modal) return;
                modal.hidden = !show;
                document.body.classList.toggle('overflow-hidden', show);
            };

            document.addEventListener('click', (event) => {
                // Open Create Bimbingan
                if (event.target.closest('[data-bimbingan-create-modal-open]')) {
                    toggleModal(bimbinganCreateModal, true);
                    return;
                }

                // Open Create Sidang
                if (event.target.closest('[data-sidang-create-modal-open]')) {
                    toggleModal(sidangCreateModal, true);
                    return;
                }

                // Open Edit Bimbingan
                const editButton = event.target.closest('[data-bimbingan-edit-modal-open]');
                if (editButton) {
                    bimbinganEditForm.action = editButton.dataset.updateUrl || '#';
                    bimbinganEditDate.value = editButton.dataset.meetingDate || '';
                    bimbinganEditNotes.value = editButton.dataset.lecturerNotes || '';
                    toggleModal(bimbinganEditModal, true);
                    return;
                }

                // Close Modals
                if (event.target.closest('[data-bimbingan-create-modal-close]')) { toggleModal(bimbinganCreateModal, false); return; }
                if (event.target.closest('[data-sidang-create-modal-close]')) { toggleModal(sidangCreateModal, false); return; }
                if (event.target.closest('[data-bimbingan-edit-modal-close]')) { toggleModal(bimbinganEditModal, false); return; }

                // PDF Preview
                const previewButton = event.target.closest('[data-preview-open]');
                if (previewButton && pdfModal && pdfFrame && pdfName) {
                    pdfFrame.src = previewButton.dataset.previewUrl || '';
                    pdfName.textContent = previewButton.dataset.previewTitle || 'Dokumen';
                    pdfModal.hidden = false;
                    document.body.classList.add('acss-modal-open');
                    return;
                }

                if (event.target.closest('[data-pdf-preview-close]') && pdfModal && pdfFrame) {
                    pdfModal.hidden = true;
                    pdfFrame.src = '';
                    document.body.classList.remove('acss-modal-open');
                    return;
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    [bimbinganEditModal, bimbinganCreateModal, sidangCreateModal].forEach(m => toggleModal(modal, false));
                    if (pdfModal && !pdfModal.hidden) {
                         pdfModal.hidden = true;
                         pdfFrame.src = '';
                         document.body.classList.remove('acss-modal-open');
                    }
                }
            });
        })();
    </script>
@endsection
