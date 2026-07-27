@extends('layouts.app')

@section('content')
    @if(session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif
    @if(session('info'))
        <div class="notice notice--warning">{{ session('info') }}</div>
    @endif
    @if ($showGradeReminder ?? false)
        <div class="notice notice--warning">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <strong>Waktu Sidang Sudah Tiba</strong>
                    <div class="">Sidang skripsi mahasiswa ini sudah berjalan. Segera isi nilai sidang skripsi Anda.</div>
                </div>
                <a class="button button--small button--success" href="{{ route('dosen.penilaian.show', $skripsi) }}">Isi Nilai</a>
            </div>
        </div>
    @endif

    @php
        $mySidangRequest = $skripsi->sidangRequests
            ->where('lecturer_id', auth()->id())
            ->where('role_type', $myRoleType ?? null)
            ->sortByDesc('created_at')
            ->first();

        $otherAdvisorApprovals = $skripsi->assignments
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->filter(function ($assignment) use ($skripsi) {
                return (int) $assignment->lecturer_id !== (int) auth()->id();
            })
            ->map(function ($assignment) use ($skripsi) {
                $latestRequest = $skripsi->sidangRequests
                    ->where('lecturer_id', $assignment->lecturer_id)
                    ->where('role_type', $assignment->role_type)
                    ->sortByDesc('created_at')
                    ->first();

                return [
                    'label' => 'Persetujuan Dosen Pembimbing '.str($assignment->role_type)->afterLast('_'),
                    'complete' => $latestRequest && $latestRequest->status !== 'rejected',
                ];
            })
            ->values();

        $showGradeAction = in_array($skripsi->current_phase, ['sidang_skripsi', 'revisi_sidang_skripsi'], true);
        $showSidangAction = in_array($myRoleType ?? null, ['pembimbing_1', 'pembimbing_2'], true)
            && $skripsi->current_phase === 'bimbingan_skripsi'
            && (! $mySidangRequest || $mySidangRequest->status === 'rejected');
        $showCancelSidangAction = in_array($myRoleType ?? null, ['pembimbing_1', 'pembimbing_2'], true)
            && $skripsi->current_phase === 'bimbingan_skripsi'
            && $mySidangRequest?->status === 'submitted';
    @endphp

    @if(in_array($myRoleType ?? null, ['pembimbing_1', 'pembimbing_2'], true) && $skripsi->current_phase === 'bimbingan_skripsi')
        @if ($mySidangRequest && $mySidangRequest->status !== 'rejected')
            <div class="notice notice--success">
                @if ($mySidangRequest->status === 'approved')
                    <strong>Permohonan sidang disetujui Kaprodi.</strong>
                @else
                    <strong>Permohonan sidang sedang diproses.</strong>
                @endif
                <div style="display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-top: .45rem;">
                    @foreach (collect([[
                        'label' => 'Persetujuan Kaprodi',
                        'complete' => $mySidangRequest->status === 'approved',
                    ]])->concat($otherAdvisorApprovals) as $approval)
                        <span class="text-xs" style="display: inline-flex; align-items: center; gap: .35rem; color: #067647;">
                            <span style="display: inline-grid; place-items: center; width: 1.15rem; height: 1.15rem; color: {{ $approval['complete'] ? '#067647' : '#b42318' }}; border: 1px solid currentColor; border-radius: 9999px;">
                                <span style="display: inline-flex; width: .72rem; height: .72rem;">@include($approval['complete'] ? 'partials.icons.check' : 'partials.icons.x')</span>
                            </span>
                            <span>{{ $approval['label'] }}</span>
                        </span>
                    @endforeach
                </div>
                @if ($showCancelSidangAction)
                    <div style="border-top: 1px solid #b7e4c7; margin-top: .75rem; padding-top: .65rem;">
                        <button class="acss-cancel-request-link" type="button" data-sidang-cancel-modal-open>
                            <span style="display: inline-flex; width: .85rem; height: .85rem;">@include('partials.icons.x')</span>
                            <span>Batalkan pengajuan sidang</span>
                        </button>
                    </div>
                @endif
            </div>
        @endif
    @endif

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_strtoupper(mb_substr($skripsi->student?->name ?? 'M', 0, 1)) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ str($skripsi->student?->name ?? '-')->title() }}</h2>
                        <p>{{ $skripsi->student?->nim ?? '-' }} • {{ $skripsi->periode?->name ?? '-' }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                        @if (($sidangProposalSchedule ?? null) || ($sidangSkripsiSchedule ?? null))
                            <div class="acss-muted" style="margin-top:.4rem; line-height:1.6;">
                                @if ($sidangProposalSchedule ?? null)
                                    <div><strong>Sidang Proposal:</strong> {{ $sidangProposalSchedule->translatedFormat('d M Y H:i') }}</div>
                                @endif
                                @if ($sidangSkripsiSchedule ?? null)
                                    <div><strong>Sidang Skripsi:</strong> {{ $sidangSkripsiSchedule->translatedFormat('d M Y H:i') }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                    <span class="status-pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                </div>
            </div>
        </div>
    </section>

    @if ($showGradeAction || $showSidangAction)
    <div class="acss-inline-actions form-actions form-actions--inline" style="display:flex; width:100%; justify-content:flex-end; margin:1.15rem 0;">
        @if($showGradeAction)
            <a class="button button--primary button--inline" href="{{ route('dosen.penilaian.show', $skripsi) }}"><span class="dosen-btn-icon">@include("partials.icons.clipboard")</span><span>Isi Nilai</span></a>
        @endif
        @if($showSidangAction)
            <button type="button" class="button button--success button--inline" data-sidang-create-modal-open><span class="dosen-btn-icon">@include("partials.icons.send")</span><span>Ajukan Permohonan Sidang</span></button>
        @endif
    </div>
    @endif

    @include('partials.skripsi-phase-timeline', ['skripsiTimelineRecord' => $skripsi, 'timelineTitle' => 'Timeline Fase Skripsi'])

    <section class="acss-crud-card">
            <div class="acss-crud-head acss-crud-head--inline">
                <div>
                    <h3 class="acss-card-title">Histori Bimbingan</h3>
                </div>
                @if(in_array($myRoleType ?? null, ['pembimbing_1','pembimbing_2']) && $skripsi->current_phase === 'bimbingan_skripsi')
                    <div class="acss-crud-head__actions">
                        <button type="button" class="button button--inline" data-bimbingan-create-modal-open><span class="dosen-btn-icon">@include("partials.icons.plus")</span><span>Tambah Bimbingan</span></button>
                    </div>
                @endif
            </div>
            <div class="acss-crud-body">
            <div class="table-shell">
                    @forelse($skripsi->bimbingans->sortByDesc('meeting_date') as $item)
                        @if ($loop->first)
                            <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi-bimb">
                                <span>Tanggal</span>
                                <span>Fase</span>
                                <span>Catatan</span>
                                <span>File PDF</span>
                            </div>
                        @endif
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

    <section class="acss-crud-card">
            <div class="acss-crud-head"><div><h3 class="acss-card-title">Dokumen Utama</h3></div></div>
            <div class="acss-crud-body">
            <div class="table-shell">
                @forelse($skripsi->documentVersions->filter(fn ($doc) => ! str_starts_with((string) $doc->phase, 'bimbingan'))->sortByDesc('created_at') as $doc)
                        @if ($loop->first)
                            <div class="table-shell__head table-shell__grid acss-table-cols-dosen-skripsi-docs">
                                <span>Tanggal</span>
                                <span>Dokumen</span>
                                <span>Versi</span>
                                <span>File PDF</span>
                            </div>
                        @endif
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
                                <div class="acss-muted text-xs ">{{ \Illuminate\Support\Str::limit($fileName, 30) }}</div>
                            </div>
                            <div class="table-shell__cell"><span class="pill">V{{ (int) ($doc->version_number ?: 1) }}</span></div>
                            <div class="table-shell__cell">
                                <div class="acss-row-actions acss-row-actions--always">
                                    <button type="button" class="text-link acss-action-link" data-preview-open data-preview-url="{{ route('documents.preview', $doc) }}" data-preview-title="{{ $fileName }}">@include('partials.icons.eye')<span>File PDF</span></button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada dokumen utama.</div>
                    @endforelse
                </div>
            </div>
    </section>

    @if ($showGradeAction || $showSidangAction)
    <div class="acss-inline-actions form-actions form-actions--inline" style="display:flex; width:100%; justify-content:flex-end; margin:1.15rem 0;">
        @if($showGradeAction)
            <a class="button button--primary button--inline" href="{{ route('dosen.penilaian.show', $skripsi) }}"><span class="dosen-btn-icon">@include("partials.icons.clipboard")</span><span>Isi Nilai</span></a>
        @endif
        @if($showSidangAction)
            <button type="button" class="button button--success button--inline" data-sidang-create-modal-open><span class="dosen-btn-icon">@include("partials.icons.send")</span><span>Ajukan Permohonan Sidang</span></button>
        @endif
    </div>
    @endif

    <div class="acss-modal" data-bimbingan-create-modal role="dialog" aria-modal="true" aria-labelledby="bimbingan-create-modal-title" hidden>
        <div class="acss-modal__backdrop" data-bimbingan-create-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title" id="bimbingan-create-modal-title">Tambah Bimbingan</h3>
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

    <div class="acss-modal" data-sidang-create-modal role="dialog" aria-modal="true" aria-labelledby="sidang-create-modal-title" hidden>
        <div class="acss-modal__backdrop" data-sidang-create-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title" id="sidang-create-modal-title">Ajukan Permohonan Sidang</h3>
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

    @if ($showCancelSidangAction)
        <div class="acss-modal" data-sidang-cancel-modal role="dialog" aria-modal="true" aria-labelledby="sidang-cancel-modal-title" hidden>
            <div class="acss-modal__backdrop" data-sidang-cancel-modal-close></div>
            <div class="acss-modal__dialog acss-modal__dialog--master">
                <div class="acss-modal__head">
                    <h3 class="acss-card-title" id="sidang-cancel-modal-title">Batalkan Pengajuan Sidang</h3>
                    <button type="button" class="acss-modal__close" data-sidang-cancel-modal-close aria-label="Tutup">×</button>
                </div>
                <form method="POST" action="{{ route('dosen.sidang-request.destroy', [$skripsi, $mySidangRequest]) }}" class="acss-form-stack-tight">
                    @csrf
                    @method('DELETE')
                    <div class="acss-master-form-shell">
                        <label class="form-field">
                            <span>Alasan Pembatalan</span>
                            <textarea name="reason" rows="5" maxlength="2000" placeholder="Jelaskan alasan pengajuan sidang dibatalkan." required></textarea>
                        </label>
                    </div>
                    <div class="form-actions form-actions--inline">
                        <button type="button" class="button button--muted button--inline" data-sidang-cancel-modal-close>Kembali</button>
                        <button class="button button--danger button--inline" type="submit">Batalkan Pengajuan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="acss-modal" data-bimbingan-edit-modal role="dialog" aria-modal="true" aria-labelledby="bimbingan-edit-modal-title" hidden>
        <div class="acss-modal__backdrop" data-bimbingan-edit-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title" id="bimbingan-edit-modal-title">Edit Bimbingan</h3>
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
            const sidangCancelModal = document.querySelector('[data-sidang-cancel-modal]');
            
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

                if (event.target.closest('[data-sidang-cancel-modal-open]')) {
                    toggleModal(sidangCancelModal, true);
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
                if (event.target.closest('[data-sidang-cancel-modal-close]')) { toggleModal(sidangCancelModal, false); return; }
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
                    [bimbinganEditModal, bimbinganCreateModal, sidangCreateModal, sidangCancelModal].forEach(modal => toggleModal(modal, false));
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
