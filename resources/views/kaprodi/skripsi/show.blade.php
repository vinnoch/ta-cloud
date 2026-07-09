@extends('layouts.app')

@section('content')
    @php
        $isProposalPhase = $skripsi->isProposalPhase();
        $hideSkripsiDefenseCards = in_array($skripsi->current_phase, ['proposal', 'sidang_proposal'], true);
        $advisorAssignments = $skripsi->assignments
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->values();
        $approvedAdvisorIds = $skripsi->sidangRequests
            ->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])
            ->where('status', 'approved')
            ->pluck('lecturer_id')
            ->filter()
            ->unique()
            ->values();
        $pendingAdvisorNames = $advisorAssignments
            ->filter(fn ($assignment) => ! $approvedAdvisorIds->contains($assignment->lecturer_id))
            ->map(fn ($assignment) => \Illuminate\Support\Str::title((string) ($assignment->lecturer?->name ?? '-')))
            ->filter()
            ->values();
        $hasAllAdvisorSidangApprovals = $advisorAssignments->isNotEmpty()
            && $approvedAdvisorIds->count() >= $advisorAssignments->pluck('lecturer_id')->filter()->unique()->count();
    @endphp

    @php
        $pendingSidangRequest = $skripsi->sidangRequests->where('status', 'submitted')->where('role_type', '!=', 'mahasiswa')->first();
        $hasReviewerFeedback = session('success')
            || $errors->any()
            || ($skripsi->isProposalPhase() && $skripsi->isRejected())
            || ($skripsi->isProposalPhase() && ! $skripsi->isApproved())
            || ! empty($pendingSidangRequest);
    @endphp

    @if ($hasReviewerFeedback)
        <div id="reviewer-feedback">
            @if (session('success'))
                <div class="notice notice--success">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="notice notice--danger">{{ $errors->first() }}</div>
            @endif
            @if ($skripsi->isProposalPhase() && $skripsi->isRejected())
                <div class="notice notice--danger">
                    <strong>Proposal sudah dikembalikan untuk revisi.</strong>
                    <div class="">Menunggu mahasiswa mengunggah revisi proposal terbaru.</div>
                </div>
            @elseif ($skripsi->isProposalPhase() && ! $skripsi->isApproved())
                <div class="notice notice--warning">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <strong>Approval Proposal</strong>
                            <div class="">Proposal mahasiswa ini menunggu persetujuan Anda untuk lanjut ke fase Sidang Proposal.</div>
                        </div>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('kaprodi.skripsi.proposal.approve', $skripsi) }}" onsubmit="return confirm('Setujui proposal ini?')">
                                @csrf
                                <button class="button button--small button--success acss-proposal-approve-button" type="submit"><span class="dosen-btn-icon">@include('partials.icons.check')</span><span>Setujui Proposal</span></button>
                            </form>
                            <button class="button button--small button--danger" type="button" onclick="document.querySelector('[data-proposal-reject-modal]').hidden = false"><span class="dosen-btn-icon">@include('partials.icons.edit')</span><span>Tolak / Revisi</span></button>
                        </div>
                    </div>
                </div>
            @endif
            @if ($pendingSidangRequest)
                <div class="notice notice--warning ">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <strong>{{ $pendingSidangRequest->lecturer?->name ?? '-' }} ({{ str($pendingSidangRequest->role_type)->replace('_', ' ')->title() }}) telah mengajukan permohonan sidang untuk skripsi ini.</strong>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('kaprodi.skripsi.sidang-request.approve', [$skripsi, $pendingSidangRequest]) }}" onsubmit="return confirm('Setujui permohonan sidang ini?')">
                                @csrf
                                <button class="button button--small button--success acss-sidang-approve-button" type="submit">Setujui Sidang</button>
                            </form>
                            <button class="button button--small button--danger" type="button" onclick="document.querySelector('[data-sidang-reject-modal]').hidden = false"><span class="dosen-btn-icon">@include('partials.icons.archive')</span><span>Tolak Sidang</span></button>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if (!empty($pendingSidangRequest))
        <div class="acss-modal" data-sidang-reject-modal hidden>
            <div class="acss-modal__backdrop" onclick="this.parentElement.hidden = true"></div>
            <div class="acss-modal__dialog acss-modal__dialog--master">
                <div class="acss-modal__head">
                    <div>
                        <h3 class="acss-card-title">Tolak Sidang</h3>
                    </div>
                    <button type="button" class="acss-modal__close" onclick="this.closest('[data-sidang-reject-modal]').hidden = true" aria-label="Tutup">×</button>
                </div>
                <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.skripsi.sidang-request.reject', [$skripsi, $pendingSidangRequest]) }}">
                    @csrf
                    <div class="acss-master-form-shell">
                        <label class="form-field">
                            <span>Catatan Penolakan</span>
                            <textarea name="note" rows="5" placeholder="Berikan catatan penolakan sidang..." required></textarea>
                        </label>
                    </div>
                    <div class="form-actions form-actions--inline">
                        <button class="button button--muted button--inline" type="button" onclick="this.closest('[data-sidang-reject-modal]').hidden = true">Batal</button>
                        <button class="button button--danger button--inline" type="submit">Kirim Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @php
        $studentNameParts = preg_split('/\s+/', trim((string) $skripsi->student->name)) ?: [];
        $avatarInitials = collect($studentNameParts)
            ->filter()
            ->take(2)
            ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    @endphp

    <section class="card card--profile acss-skripsi-detail-topcompact">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ $avatarInitials !== '' ? $avatarInitials : mb_strtoupper(mb_substr((string) $skripsi->student->name, 0, 1)) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ \Illuminate\Support\Str::title((string) $skripsi->student->name) }}</h2>
                        <p>{{ $skripsi->student->nim ?? '-' }} •
                            {{ $skripsi->periode?->name ?? ($skripsi->periode?->kode_periode ?? '-') }}</p>
                        <div class="acss-quote-title">{{ \Illuminate\Support\Str::title((string) $skripsi->title) }}</div>
                        @if (($proposalVersions ?? collect())->isNotEmpty() || $skripsi->isProposalPhase())
                            <div class="acss-link-gap-top">
                                <a href="{{ route('kaprodi.skripsi.proposal', $skripsi) }}" class="acss-link-subtle acss-link-subtle--icon" target="_blank" rel="noopener noreferrer">
                                    <span class="acss-link-subtle__icon">@include('partials.icons.file')</span>
                                    <span>Lihat Proposal</span>
                                </a>
                            </div>
                        @endif
                    </div>
                    <span class="status-pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                </div>
            </div>
        </div>

    </section>

    @include('partials.skripsi-phase-timeline', ['skripsiTimelineRecord' => $skripsi, 'timelineTitle' => 'Timeline Skripsi'])

    @if ($isProposalPhase)
        <div class="acss-detail-pair-grid">
            <section class="card" id="riwayat-proposal">
                <div class="section-heading">
                    <div>
                        <h3 class="acss-card-title">Riwayat Proposal</h3>
                    </div>
                </div>
                <div class="table-shell table-shell--proposal-docs">
                    <div class="table-shell__head table-shell__grid acss-table-cols-kaprodi-proposal-history">
                        <span>Tanggal</span>
                        <span>Status Upload</span>
                    </div>
                    @forelse (($proposalVersions ?? [])->sortByDesc('version_number') as $document)
                        <div class="table-shell__row table-shell__grid acss-table-cols-kaprodi-proposal-history acss-hover-row-group">
                            <div class="table-shell__cell">
                                <strong>{{ $document->created_at?->format('d/m/Y') ?? '-' }}</strong>
                                <div class="text-[10px] acss-muted">{{ $document->created_at?->format('H:i') ?? '' }}</div>
                            </div>
                            <div class="table-shell__cell">
                                <div>{{ $document->version_number <= 1 ? 'Upload Baru' : 'Revisi ' . ($document->version_number - 1) }}</div>
                                <div class="acss-row-actions acss-row-actions--always">
                                    <button type="button" class="text-link acss-action-link" onclick="openPdfModal('{{ route('documents.preview', $document) }}', 'Proposal v{{ $document->version_number }}')">
                                        @include('partials.icons.file')<span>Proposal</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada proposal yang diunggah.</div>
                    @endforelse
                </div>
            </section>

            @if ($skripsi->current_phase !== 'proposal' || $skripsi->proposal_review_status === 'approved')
                <section class="card">
                    <div class="section-heading acss-crud-head--inline">
                        <div>
                            <h3 class="acss-card-title">Reviewer</h3>
                        </div>
                        <button type="button" class="acss-link-subtle acss-link-subtle--icon" data-reviewer-modal-open><span class="acss-link-subtle__icon">@include('partials.icons.plus')</span><span>Tambahkan</span></button>
                    </div>
                    <div id="reviewer-list">{!! $reviewerTableHtml !!}</div>
                </section>
            @endif
        </div>
    @endif

    @if (! $isProposalPhase)
    <div class="acss-stack-sections">
        @if ($skripsi->current_phase === 'review_dokumen_final')
            <section class="card card--notice acss-final-review-card">
                <div class="section-heading">
                    <div>
                        <h3>Validasi Dokumen Final</h3>
                    </div>
                </div>
                <div class="acss-final-review-card__body acss-final-review-card__body--split">
                    <div class="acss-final-review-card__content">
                        <p class="acss-final-review-card__intro">Seluruh reviewer telah menyetujui dokumen final. Lakukan validasi akhir untuk menyatakan skripsi selesai.</p>
                        <div class="flex gap-2">
                            <form method="POST" action="{{ route('kaprodi.skripsi.final-review.approve', $skripsi) }}" onsubmit="return confirm('Validasi dokumen final dan selesaikan skripsi?')">
                                @csrf
                                <button class="button button--small acss-final-review-card__button" type="submit">Validasi & Selesaikan Skripsi</button>
                            </form>
                        </div>
                    </div>

                    <div class="acss-final-review-card__docs">
                        <div class="acss-final-documents">
                            @forelse ($finalReviewDocuments as $document)
                                <div class="acss-final-documents__item">
                                    <div>
                                        <strong>{{ str($document->phase)->replace('_', ' ')->title() }}</strong>
                                        <small>{{ $document->created_at?->format('d/m/Y H:i') ?? '-' }} · {{ $document->uploader?->name ?? 'Mahasiswa' }}</small>
                                    </div>
                                    <button type="button" class="text-link acss-action-link" onclick="openPdfModal('{{ route('documents.preview', $document) }}', '{{ str($document->phase)->replace('_', ' ')->title() }}')">@include('partials.icons.eye')<span>Dokumen PDF</span></button>
                                </div>
                            @empty
                                <div class="empty-state">Belum ada dokumen final yang terunggah.</div>
                            @endforelse

                            @if ($journalArticleUrl)
                                <div class="acss-final-documents__item">
                                    <div>
                                        <strong>Artikel Jurnal</strong>
                                        <small>Tautan artikel jurnal yang dikirim mahasiswa.</small>
                                    </div>
                                    <a class="text-link acss-action-link" href="{{ $journalArticleUrl }}" target="_blank" rel="noopener noreferrer">@include('partials.icons.eye')<span>Buka Tautan</span></a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        @endif

        @if (! $hideSkripsiDefenseCards)
        <div class="acss-detail-pair-grid">
            @php
                $canShowScheduleCard = in_array($skripsi->current_phase, ['sidang_proposal', 'sidang_skripsi', 'revisi_sidang_skripsi', 'review_dokumen_final', 'skripsi_selesai'], true);
            @endphp
            <section class="card">
                <div class="section-heading acss-crud-head--inline">
                    <div>
                        <h3>Reviewer</h3>
                    </div>
                    <button type="button" class="acss-link-subtle acss-link-subtle--icon" data-reviewer-modal-open><span class="acss-link-subtle__icon">@include('partials.icons.plus')</span><span>Tambahkan</span></button>
                </div>
                <div id="reviewer-list">{!! $reviewerTableHtml !!}</div>
            </section>

            <section class="card">
                <div class="section-heading acss-crud-head--inline">
                    <div>
                        <h3>Bimbingan</h3>
                    </div>
                    <div class="acss-inline-actions">
                        <a class="acss-link-subtle" href="{{ route('kaprodi.skripsi.bimbingan', $skripsi) }}">Lihat Histori</a>
                        <a class="acss-link-subtle" href="{{ route('kaprodi.skripsi.logbook', $skripsi) }}">Download Logbook</a>
                    </div>
                </div>
                <div class="table-shell">
                    @if (count($latestBimbingans ?? []) > 0)
                        <div class="table-shell__head table-shell__grid" style="--table-cols:repeat(3,minmax(0,1fr));">
                            <span>Tanggal</span>
                            <span>Reviewer</span>
                            <span>Catatan</span>
                        </div>
                    @endif
                    @forelse (($latestBimbingans ?? []) as $bimbingan)
                        <div class="table-shell__row table-shell__grid" style="--table-cols:repeat(3,minmax(0,1fr));">
                            <div class="table-shell__cell"><strong>{{ $bimbingan->meeting_date?->format('d/m/Y') ?? '-' }}</strong></div>
                            <div class="table-shell__cell">{{ $bimbingan->reviewer?->name ?? '-' }}</div>
                            <div class="table-shell__cell">{{ \Illuminate\Support\Str::limit($bimbingan->lecturer_notes ?: '-', 80) }}</div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada histori bimbingan.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <div class="acss-detail-pair-grid">
            <section class="card">
                <div class="section-heading">
                    <div>
                        <h3>Pengajuan Sidang</h3>
                    </div>
                </div>
                <div class="table-shell">
                    @if (count($sidangRequests ?? []) > 0)
                        <div class="table-shell__head table-shell__grid acss-table-cols-pengajuan-sidang">
                            <span>Tanggal</span>
                            <span>Reviewer</span>
                        </div>
                    @endif
                    @forelse (($sidangRequests ?? []) as $sidangRequest)
                        <div class="table-shell__row table-shell__grid acss-table-cols-pengajuan-sidang">
                            <div class="table-shell__cell">
                                <div>{{ $sidangRequest->submitted_at?->format('d/m/Y') ?? '-' }}</div>
                                @if ($sidangRequest->status === 'approved')
                                    <div class="acss-muted text-xs">disetujui Kaprodi {{ $sidangRequest->updated_at?->format('d/m/Y') ?? '-' }}</div>
                                @endif
                            </div>
                            <div class="table-shell__cell">
                                <div><strong>{{ \Illuminate\Support\Str::title((string) ($sidangRequest->lecturer?->name ?? '-')) }}</strong></div>
                                <div class="acss-muted text-xs">{{ str($sidangRequest->role_type)->replace('_', ' ')->title() }}</div>
                                @if ($sidangRequest->status !== 'approved')
                                    <div class="acss-row-actions acss-row-actions--always acss-row-actions--compact">
                                        <form method="POST" action="{{ route('kaprodi.skripsi.sidang-request.approve', [$skripsi, $sidangRequest]) }}" onsubmit="return confirm('Setujui permohonan sidang ini?')">
                                            @csrf
                                            <button class="button button--small button--success" type="submit">Approve</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="empty-state">Belum ada permohonan sidang.</div>
                    @endforelse
                </div>
                @if (! $isProposalPhase && $pendingAdvisorNames->isNotEmpty() && $skripsi->sidangRequests->whereIn('role_type', ['pembimbing_1', 'pembimbing_2'])->where('status', 'approved')->isNotEmpty())
                    <div class="acss-link-gap-top text-xs" style="color: var(--danger);">
                        Menunggu pengajuan sidang oleh dosen pembimbing lain.
                    </div>
                @endif
            </section>

            <section class="card {{ $hasAllAdvisorSidangApprovals ? '' : 'acss-section-card--inactive' }}">
                <div class="section-heading">
                <div>
                    <h3>Set Jadwal Sidang</h3>
                </div>
            </div>
            @php
                $isProposalSchedule = $skripsi->current_phase === 'sidang_proposal';
                $scheduleField = $isProposalSchedule ? 'sidang_proposal_datetime' : 'sidang_skripsi_datetime';
                $activeSchedule = $isProposalSchedule ? ($sidangProposalSchedule ?? null) : ($sidangSkripsiSchedule ?? null);
                $scheduleRoute = $isProposalSchedule
                    ? route('kaprodi.skripsi.sidang-proposal-schedule.update', $skripsi)
                    : route('kaprodi.skripsi.sidang-schedule.update', $skripsi);
                $scheduleLabel = $isProposalSchedule ? 'Sidang Proposal' : 'Sidang Skripsi';
                $defaultSidangSchedule = $activeSchedule
                    ? $activeSchedule->format('Y-m-d\TH:i')
                    : now()->addDay()->setTime(8, 0)->format('Y-m-d\TH:i');
            @endphp
            @if (! $hasAllAdvisorSidangApprovals)
                <div class="acss-muted acss-sidang-schedule-current">
                    Jadwal sidang aktif setelah seluruh dosen pembimbing mengajukan dan disetujui Kaprodi.
                </div>
            @elseif ($activeSchedule)
                <div class="acss-muted acss-sidang-schedule-current">
                    Jadwal aktif {{ $scheduleLabel }}: <strong>{{ $activeSchedule->translatedFormat('d M Y H:i') }}</strong>
                </div>
            @endif
                <form method="POST" action="{{ $scheduleRoute }}" class="acss-master-form-shell acss-sidang-schedule-form acss-sidang-schedule-form--stacked">
                    @csrf
                    @method('PUT')
                    <label class="form-field acss-sidang-schedule-field">
                        <div class="acss-datetime-picker {{ $activeSchedule || ! $hasAllAdvisorSidangApprovals ? 'is-disabled' : '' }}" data-acss-datetime-picker data-value="{{ old($scheduleField, $defaultSidangSchedule) }}" data-min="{{ now()->format('Y-m-d\TH:i') }}" data-locked="{{ $activeSchedule || ! $hasAllAdvisorSidangApprovals ? 'true' : 'false' }}">
                            <button type="button" class="acss-datetime-picker__trigger" data-acss-datetime-trigger aria-haspopup="dialog" aria-expanded="false" @disabled(! $hasAllAdvisorSidangApprovals)>
                                <span class="acss-datetime-picker__icon" aria-hidden="true"><svg viewBox="0 0 20 20" fill="none"><path d="M6.667 1.667v2.5M13.333 1.667v2.5M2.5 7.083h15M4.583 3.75h10.834A1.25 1.25 0 0 1 16.667 5v10.417a1.25 1.25 0 0 1-1.25 1.25H4.583a1.25 1.25 0 0 1-1.25-1.25V5a1.25 1.25 0 0 1 1.25-1.25Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                                <span class="acss-datetime-picker__value" data-acss-datetime-value></span>
                            </button>
                            <input type="hidden" name="{{ $scheduleField }}" value="{{ old($scheduleField, $defaultSidangSchedule) }}" data-acss-datetime-input required>
                            <div class="acss-datetime-picker__panel" data-acss-datetime-panel hidden>
                                <div class="acss-datetime-picker__head">
                                    <button type="button" class="acss-datetime-picker__nav" data-acss-datetime-prev aria-label="Bulan sebelumnya">‹</button>
                                    <strong data-acss-datetime-label></strong>
                                    <button type="button" class="acss-datetime-picker__nav" data-acss-datetime-next aria-label="Bulan berikutnya">›</button>
                                </div>
                                <div class="acss-datetime-picker__weekdays"><span>Min</span><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span></div>
                                <div class="acss-datetime-picker__days" data-acss-datetime-days></div>
                                <div class="acss-datetime-picker__time">
                                    <label class="form-field acss-field-tight"><span>Jam</span><select data-acss-datetime-hour></select></label>
                                    <label class="form-field acss-field-tight"><span>Menit</span><select data-acss-datetime-minute></select></label>
                                </div>
                                <div class="acss-datetime-picker__actions"><button type="button" class="button button--inline" data-acss-datetime-apply>Pilih Jadwal</button></div>
                            </div>
                        </div>
                        @error($scheduleField)
                            <small class="field-error" style="color: #b42318; display: block; margin-top: 0.25rem;">{{ $message }}</small>
                        @enderror
                    </label>
                    <div class="form-actions form-actions--inline acss-sidang-schedule-actions">
                        <button class="button button--inline" type="{{ $activeSchedule ? 'button' : 'submit' }}" data-sidang-schedule-toggle @disabled(! $hasAllAdvisorSidangApprovals)>{{ $activeSchedule ? 'Edit Jadwal' : 'Simpan Jadwal' }}</button>
                    </div>
                </form>
            </section>
            @endif
        </div>
        @if (! $hideSkripsiDefenseCards)
        <section class="card">
            <div class="section-heading acss-crud-head--inline">
                <div>
                    <h3>Penilaian Sidang Skripsi</h3>
                </div>
                <a class="acss-link-subtle" href="{{ route('kaprodi.nilai.index', ['skripsi_id' => $skripsi->id]) }}">Lihat Semua Nilai</a>
            </div>
            <div class="acss-grading-progress-grid">
                <div class="acss-grading-progress-card acss-grading-progress-card--stacked">
                    <div class="acss-grading-progress-head">
                        <span class="acss-grading-progress-label">Sudah Mengirim</span>
                        <strong class="acss-grading-progress-count">{{ $gradingProgress['submitted_count'] }}/{{ $gradingProgress['expected_count'] }}</strong>
                    </div>
                    <div class="acss-grading-progress-lists ">
                        <div>
                            <div class="acss-grading-pill-wrap">
                                @forelse ($gradingProgress['submitted_reviewers'] as $reviewer)
                                    <span class="pill pill--blue">{{ $reviewer['name'] }} • {{ $reviewer['role'] }}</span>
                                @empty
                                    <span class="acss-grading-inline-empty">Belum ada dosen yang mengirim nilai.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="acss-grading-progress-card acss-grading-progress-card--stacked">
                    <div class="acss-grading-progress-head">
                        <span class="acss-grading-progress-label">Belum Mengirim</span>
                    </div>
                    <div class="acss-grading-progress-lists ">
                        <div>
                            <div class="acss-grading-pill-wrap">
                                @forelse ($gradingProgress['pending_reviewers'] as $reviewer)
                                    <span class="pill">{{ $reviewer['name'] }} • {{ $reviewer['role'] }}</span>
                                @empty
                                    @if (($gradingProgress['expected_count'] ?? 0) === 0)
                                        <span class="acss-grading-inline-empty">Belum ada dosen penilai yang ditetapkan.</span>
                                    @else
                                        <span class="acss-grading-inline-empty">Semua dosen penilai sudah mengirim nilai.</span>
                                    @endif
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        @endif

        @if (! $hideSkripsiDefenseCards)
        <section class="card">
            <div class="section-heading">
                <div>
                    <h3>Artikel Jurnal Terpublikasi</h3>
                </div>
            </div>
            @if ($journalArticleUrl)
                <div class="acss-journal-link">
                    <a href="{{ $journalArticleUrl }}" target="_blank" rel="noopener noreferrer" class="acss-journal-link__anchor">
                        <span class="acss-journal-link__icon" aria-hidden="true">@include('partials.icons.link-out')</span>
                        <span>{{ $journalArticleUrl }}</span>
                    </a>
                </div>
            @else
                <div class="empty-state">Belum ada link artikel jurnal.</div>
            @endif
        </section>
        @endif
    </div>
    @endif

    <div class="acss-modal" data-reviewer-modal hidden>
        <div class="acss-modal__backdrop" data-reviewer-modal-close></div>
        <div class="acss-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambahkan Reviewer</h3>
                </div>
                <button type="button" class="acss-modal__close" data-reviewer-modal-close aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ $reviewerStoreUrl }}"
                id="assign-reviewer-form" data-store-url="{{ $reviewerStoreUrl }}"
                data-search-url="{{ $reviewerSearchUrl }}">
                @csrf
                <div id="reviewer-modal-feedback"></div>
                <label class="form-field">
                    <span>Dosen</span>
                    <div class="reviewer-search-container acss-relative">
                        <input type="text" class="reviewer-search" placeholder="Cari dosen..." autocomplete="off">
                        <input type="hidden" name="lecturer_id" required>
                        <ul class="reviewer-results acss-reviewer-results"></ul>
                    </div>
                </label>
                <label class="form-field">
                    <span>Peran</span>
                    <select name="role_type" id="assign-reviewer-role" required>
                        <option value="pembimbing_1">Dosen Pembimbing 1</option>
                        <option value="pembimbing_2">Dosen Pembimbing 2</option>
                        <option value="penguji_1">Penguji 1</option>
                        <option value="penguji_2">Penguji 2</option>
                    </select>
                </label>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline"
                        data-reviewer-modal-close>Batal</button>
                    <button class="button button--inline" type="submit" id="assign-reviewer-button">Tambahkan
                        Reviewer</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        (() => {
            const feedback = document.getElementById('reviewer-feedback');
            const modalFeedback = document.getElementById('reviewer-modal-feedback');
            const reviewerList = document.getElementById('reviewer-list');
            const reviewerModal = document.querySelector('[data-reviewer-modal]');
            const reviewerForm = document.getElementById('assign-reviewer-form');
            const submitButton = document.getElementById('assign-reviewer-button');
            const body = document.body;

            const setBodyLocked = () => {
                const hasOpenModal = document.querySelector('.acss-modal:not([hidden])');
                body.classList.toggle('overflow-hidden', Boolean(hasOpenModal));
            };

            const toggleNamedModal = (modal, show) => {
                if (!modal) {
                    return;
                }

                modal.hidden = !show;
                setBodyLocked();
            };

            const showMessage = (message, type = 'success', target = 'page') => {
                const targetNode = target === 'modal' ? modalFeedback : feedback;
                if (!targetNode) {
                    return;
                }

                targetNode.innerHTML = `<div class="notice notice--${type}">${message}</div>`;
            };

            const clearMessage = (target = 'page') => {
                const targetNode = target === 'modal' ? modalFeedback : feedback;
                if (targetNode) {
                    targetNode.innerHTML = '';
                }
            };

            document.addEventListener('click', (event) => {

                if (event.target.closest('[data-reviewer-modal-open]')) {
                    event.preventDefault();
                    toggleNamedModal(reviewerModal, true);
                    clearMessage('modal');
                    reviewerForm?.querySelector('.reviewer-search')?.focus();
                    return;
                }

                if (event.target.closest('[data-reviewer-modal-close]')) {
                    toggleNamedModal(reviewerModal, false);
                    return;
                }

                if (!event.target.closest('.reviewer-search-container')) {
                    reviewerForm?.querySelector('.reviewer-results')?.style.setProperty('display', 'none');
                }
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape') {
                    return;
                }

                document.querySelectorAll('.acss-modal:not([hidden])').forEach((modal) => {
                    modal.hidden = true;
                });
                setBodyLocked();
            });

            if (!reviewerForm || !reviewerList || !submitButton) {
                return;
            }

            const searchUrl = reviewerForm.dataset.searchUrl;
            const storeUrl = reviewerForm.dataset.storeUrl;
            const csrfToken = reviewerForm.querySelector('input[name="_token"]').value;
            const searchInput = reviewerForm.querySelector('.reviewer-search');
            const hiddenInput = reviewerForm.querySelector('input[name="lecturer_id"]');
            const resultsList = reviewerForm.querySelector('.reviewer-results');
            let debounceTimer;

            const bindRemoveButtons = () => {
                reviewerList.querySelectorAll('.reviewer-remove-button').forEach((button) => {
                    button.onclick = async () => {
                        if (!await window.taConfirm('Remove reviewer ini?', 'Remove')) {
                            return;
                        }

                        clearMessage();

                        const response = await fetch(button.dataset.url, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                Accept: 'application/json',
                            },
                            body: new URLSearchParams({
                                _token: csrfToken,
                                _method: 'DELETE',
                            }),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            showMessage(data.message || 'Gagal unassign reviewer.', 'danger');
                            return;
                        }

                        reviewerList.innerHTML = data.reviewers_html;
                        showMessage(data.message || 'Reviewer berhasil di-remove.');
                        bindRemoveButtons();
                    };
                });
            };

            searchInput?.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                hiddenInput.value = '';
                const query = searchInput.value.trim();

                if (query.length < 2) {
                    resultsList.style.display = 'none';
                    resultsList.innerHTML = '';
                    return;
                }

                debounceTimer = setTimeout(async () => {
                    const response = await fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json',
                        },
                    });

                    const lecturers = await response.json().catch(() => []);
                    resultsList.innerHTML = '';

                    lecturers.forEach((lecturer) => {
                        const item = document.createElement('li');
                        item.className = 'acss-reviewer-result-item';
                        item.innerHTML =
                            `<strong>${lecturer.name}</strong><br><small>${lecturer.email}</small>`;
                        item.onclick = () => {
                            searchInput.value = lecturer.name;
                            hiddenInput.value = lecturer.id;
                            resultsList.style.display = 'none';
                        };
                        resultsList.appendChild(item);
                    });

                    resultsList.style.display = lecturers.length ? 'block' : 'none';
                }, 250);
            });

            reviewerForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                clearMessage('modal');

                if (!hiddenInput.value) {
                    showMessage('Pilih dosen dari hasil pencarian dulu.', 'danger', 'modal');
                    searchInput.focus();
                    return;
                }

                submitButton.disabled = true;

                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        Accept: 'application/json',
                    },
                    body: new FormData(reviewerForm),
                });

                const data = await response.json().catch(() => ({}));
                submitButton.disabled = false;

                if (!response.ok) {
                    const errorMessage = data.message || data.errors?.lecturer_id?.[0] || data.errors
                        ?.role_type?.[0] || 'Gagal menyimpan reviewer.';
                    showMessage(errorMessage, 'danger', 'modal');
                    return;
                }

                reviewerList.innerHTML = data.reviewers_html;
                reviewerForm.reset();
                resultsList.innerHTML = '';
                resultsList.style.display = 'none';
                toggleNamedModal(reviewerModal, false);
                showMessage(data.message || 'Reviewer berhasil ditetapkan.');
                bindRemoveButtons();
            });

            bindRemoveButtons();
        })();
    </script>

    <div class="acss-modal" data-proposal-reject-modal hidden>
        <div class="acss-modal__backdrop" onclick="this.parentElement.hidden = true"></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tolak / Revisi Proposal</h3>
                </div>
                <button type="button" class="acss-modal__close" onclick="this.closest('[data-proposal-reject-modal]').hidden = true" aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.skripsi.proposal.reject', $skripsi) }}">
                @csrf
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Catatan Revisi</span>
                        <textarea name="note" rows="5" placeholder="Berikan catatan perbaikan untuk mahasiswa..." required></textarea>
                    </label>
                </div>
                <div class="form-actions form-actions--inline">
                    <button class="button button--muted button--inline" type="button" onclick="this.closest('[data-proposal-reject-modal]').hidden = true">Batal</button>
                    <button class="button button--danger button--inline" type="submit">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>

    @include('partials.pdf-viewer-modal')
@endsection
