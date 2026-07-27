@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="notice notice--danger">{{ session('error') }}</div>
    @endif

    @if ($activeSkripsi)
        @php
            $studentNameParts = preg_split('/\s+/', trim((string) ($activeSkripsi->student->name ?? ''))) ?: [];
            $avatarInitials = collect($studentNameParts)->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('');
            $proposalVersions = $activeSkripsi->documentVersions()
                ->where('phase', 'proposal')
                ->orderByDesc('version_number')
                ->get();
            $isProposalPhase = $activeSkripsi->isProposalPhase();
            $canProposalUpload = $activeSkripsi->isProposalPhase()
                && ! $activeSkripsi->isApproved();
            $needsProposalUpload = $canProposalUpload && $proposalVersions->isEmpty();
            $isProposalProcessing = $activeSkripsi->isProposalPhase() && $activeSkripsi->isProcessing();
            $needsProposalRevision = $activeSkripsi->isProposalPhase() && $activeSkripsi->isRejected();
            $isDraftProposal = $activeSkripsi->isProposalPhase() && $activeSkripsi->isDraft();
        @endphp

        @if ($activeSkripsi->isDraft())
            <div class="notice notice--danger">Proposal ini belum diajukan dan masih tersimpan sebagai Draft</div>
        @elseif ($activeSkripsi->isRejected())
            <div class="notice notice--danger">
                <strong>Proposal perlu direvisi.</strong>
                <div class="">{{ $activeSkripsi->proposal_review_note ?: 'Kaprodi telah meminta Anda mengunggah revisi proposal terbaru.' }}</div>
            </div>
        @elseif ($activeSkripsi->isProcessing())
            <div class="notice notice--danger">Pengajuan Proposal sedang diproses oleh Kaprodi.</div>
        @endif

        <section class="card card--profile">
            <div class="profile-card">
                <div class="profile-card__avatar">{{ $avatarInitials !== '' ? $avatarInitials : 'M' }}</div>
                <div class="profile-card__main">
                    <div class="profile-card__meta">
                        <div>
                            <h2>{{ \Illuminate\Support\Str::title((string) ($activeSkripsi->student->name ?? '-')) }}</h2>
                            <p>{{ $activeSkripsi->student->nim ?? '-' }} • {{ $activeSkripsi->periode?->name ?? ($activeSkripsi->periode?->kode_periode ?? '-') }}</p>
                            <div class="acss-quote-title">{{ \Illuminate\Support\Str::title((string) $activeSkripsi->title) }}</div>
                        </div>
                        <div class="acss-profile-badges">
                            <span class="status-pill">{{ str($activeSkripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        @if (! $isProposalPhase)
            @include('partials.skripsi-phase-timeline', ['skripsiTimelineRecord' => $activeSkripsi, 'timelineTitle' => 'Timeline Skripsi'])
        @endif

        @if (!empty($needsProposalUpload) && !empty(route('mahasiswa.skripsi.show', $activeSkripsi)))
            <section class="acss-section-card">
                <div class="acss-section-card__head">
                    <div>
                        <strong class="text-red-600 font-bold uppercase tracking-wide">{{ $needsProposalRevision ? 'Proposal perlu direvisi.' : ($isDraftProposal ? 'Proposal Anda tersimpan sebagai draft.' : 'Anda belum mengirimkan Proposal.') }}</strong>
                        <p class="acss-muted">{{ $needsProposalRevision ? 'Upload revisi proposal terbaru agar proses review dapat dilanjutkan.' : ($isDraftProposal ? 'Publikasikan untuk mengirim ke Kaprodi.' : 'Upload sekarang agar proses review bisa dimulai.') }}</p>
                    </div>
                    @if (! $isDraftProposal)
                        <a href="{{ route('mahasiswa.skripsi.show', ['skripsi' => $activeSkripsi, 'openProposalUpload' => '1']) }}" class="button button--inline">{{ $needsProposalRevision ? 'Upload Revisi Proposal' : 'Upload Proposal' }}</a>
                    @endif
                </div>
            </section>
        @endif

        <section class="acss-crud-card" id="proposal-skripsi">
            <div class="acss-crud-head acss-crud-head--inline">
                <div>
                    <h3 class="acss-card-title">Proposal Skripsi</h3>
                </div>
                @if ($needsProposalRevision)
                    <div class="acss-crud-head__actions">
                        <a href="{{ route('mahasiswa.skripsi.show', ['skripsi' => $activeSkripsi, 'openProposalUpload' => '1']) }}" class="button button--inline">Upload Revisi Proposal</a>
                    </div>
                @endif
            </div>

            <div class="acss-crud-body">
            <div class="table-shell table-shell--proposal-docs">
                @forelse (($proposalVersions ?? []) as $document)
                    @if ($loop->first)
                        <div class="table-shell__head table-shell__grid acss-table-cols-proposal-docs-detail">
                            <span>Tanggal</span>
                            <span>Versi</span>
                            <span>Catatan</span>
                            <span>File PDF</span>
                        </div>
                    @endif
                    <div class="table-shell__row table-shell__grid acss-table-cols-proposal-docs-detail acss-hover-row-group">
                        <div class="table-shell__cell">
                            <strong>{{ $document->created_at?->format('d/m/Y') ?? '-' }}</strong>
                            <div class="text-[10px] acss-muted">{{ $document->created_at?->format('H:i') ?? '' }}</div>
                        </div>
                        <div class="table-shell__cell"><span class="pill">V{{ $document->version_number }}</span></div>
                        <div class="table-shell__cell">{{ $document->version_number <= 1 ? 'Upload Baru' : 'Revisi ' . ($document->version_number - 1) }}</div>
                        <div class="table-shell__cell table-shell__cell--action">
                            @php $fileUrl = route('mahasiswa.skripsi.proposal.file', [$activeSkripsi, $document]); @endphp
                            <button type="button" class="text-link acss-action-link" data-preview-open data-preview-url="{{ $fileUrl }}" data-preview-title="Proposal v{{ $document->version_number }}">
                                @include('partials.icons.eye')<span>File PDF</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada proposal yang diunggah.</div>
                @endforelse
            </div>

            @if ($isDraftProposal)
                <div class="form-actions mt-4 pb-4">
                    <button type="button" class="button button--inline button--muted" data-draft-edit-modal-open>
                        <span class="dosen-btn-icon">@include('partials.icons.edit')</span>
                        <span>Edit Proposal</span>
                    </button>
                    <form action="{{ route('mahasiswa.skripsi.publish', $activeSkripsi) }}" method="POST">
                        @csrf
                        <button type="submit" class="button button--inline">
                            <span class="dosen-btn-icon">@include('partials.icons.send')</span>
                            <span>Ajukan Proposal</span>
                        </button>
                    </form>
                </div>
            @endif
            </div>
        </section>

        @if (! $isProposalPhase)
        <div class="acss-stack-sections ">
            <div class="acss-detail-pair-grid">
            <section class="acss-crud-card {{ $isProposalPhase ? 'acss-section-card--inactive' : '' }}">
                <div class="acss-crud-head"><div><h3 class="acss-card-title">Dosen Pembimbing</h3></div></div>
                <div class="acss-crud-body">
                    <div class="table-shell">
                        @forelse($activeSkripsi->assignments()->with('lecturer')->get() as $assignment)
                            @if ($loop->first)
                                <div class="table-shell__head table-shell__grid acss-table-cols-mhs-skripsi-reviewers">
                                    <span>Peran</span>
                                    <span>Nama Dosen</span>
                                </div>
                            @endif
                            <div class="table-shell__row table-shell__grid acss-table-cols-mhs-skripsi-reviewers acss-hover-row-group">
                                <div class="table-shell__cell">
                                    <strong>{{ str($assignment->role_type)->replace('_', ' ')->title() }}</strong>
                                    <div class="acss-row-actions">
                                        <a href="{{ route('mahasiswa.skripsi.bimbingan.index', ['skripsi' => $activeSkripsi->id, 'reviewer_id' => $assignment->lecturer_id]) }}" class="acss-action-link">
                                            @include('partials.icons.eye')
                                            <span>Histori Bimbingan</span>
                                        </a>
                                    </div>
                                </div>
                                <div class="table-shell__cell">
                                    <strong>{{ \Illuminate\Support\Str::title((string) $assignment->lecturer->name) }}</strong>
                                    <small>assigned {{ $assignment->created_at?->format('d/m/Y') ?? '-' }}</small>
                                </div>
                            </div>
                        @empty
                            <div class="empty-state">Belum ada reviewer ditetapkan.</div>
                        @endforelse
                    </div>
                </div>
            </section>

            <section class="acss-crud-card {{ $isProposalPhase ? 'acss-section-card--inactive' : '' }}">
                <div class="acss-crud-head acss-crud-head--inline">
                    <div><h3 class="acss-card-title">Histori Bimbingan</h3></div>
                    <a href="{{ route('mahasiswa.skripsi.bimbingan.index', $activeSkripsi) }}" class="acss-link-subtle">Lihat Histori</a>
                </div>
                <div class="acss-crud-body">
                    <div class="table-shell">
                        @forelse($activeSkripsi->bimbingans()->with(['reviewer', 'reviewedVersion'])->latest('meeting_date')->limit(5)->get() as $bimbingan)
                            @if ($loop->first)
                                <div class="table-shell__head table-shell__grid acss-table-cols-mhs-skripsi-bimbingan">
                                    <span>Tanggal</span>
                                    <span>Dosen</span>
                                    <span>Catatan</span>
                                </div>
                            @endif
                            <div class="table-shell__row table-shell__grid acss-table-cols-mhs-skripsi-bimbingan acss-hover-row-group">
                                <div class="table-shell__cell">
                                    <strong>{{ $bimbingan->meeting_date->format('d/m/Y') }}</strong>
                                    <small class="acss-time-sub">{{ $bimbingan->meeting_date->format('H:i') }}</small>
                                </div>
                                <div class="table-shell__cell">{{ $bimbingan->reviewer->name }}</div>
                                <div class="table-shell__cell">{{ Str::limit($bimbingan->lecturer_notes ?? '-', 60) }}</div>
                            </div>
                        @empty
                            <div class="empty-state">Belum ada histori bimbingan.</div>
                        @endforelse
                    </div>
                </div>
            </section>
            </div>
        @endif
    @else
        @if (isset($completedSkripsi) && $completedSkripsi)
            <div class="notice notice--success">Selamat! Skripsi Anda telah selesai.</div>

            <section class="card card--profile">
                <div class="profile-card">
                    <div class="profile-card__avatar">{{ mb_strtoupper(mb_substr($completedSkripsi->student->name ?? 'M', 0, 1)) }}</div>
                    <div class="profile-card__main">
                        <div class="profile-card__meta">
                            <div>
                                <h2>{{ str($completedSkripsi->student->name ?? '-')->title() }}</h2>
                                <p>{{ $completedSkripsi->student->nim ?? '-' }} • {{ $completedSkripsi->periode?->name ?? ($completedSkripsi->periode?->kode_periode ?? '-') }}</p>
                                <div class="acss-quote-title">{{ $completedSkripsi->title }}</div>
                            </div>
                            <div class="acss-profile-badges">
                                <span class="status-pill">{{ str($completedSkripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div class="acss-form-actions">
                <a href="{{ route('mahasiswa.skripsi.show', $completedSkripsi) }}" class="button button--primary button--inline">Lihat Detail Skripsi</a>
            </div>
        @else
            <section class="acss-section-card">
                <div class="acss-section-card__head">
                    <div>
                        <h1 class="acss-page-title">Skripsi Tidak Ditemukan</h1>
                        <p class="acss-muted">Silakan buat pengajuan skripsi baru untuk memulai.</p>
                    </div>
                </div>
                <div class="acss-section-card__body">
                    <div class="acss-form-actions">
                        <a href="{{ route('mahasiswa.skripsi.create') }}" class="button button--success button--inline">Ajukan Skripsi Baru</a>
                    </div>
                </div>
            </section>
        @endif
    @endif

    @include('partials.pdf-viewer-modal')
    @include('mahasiswa.bimbingan.partials.revision-upload-script', ['readOnly' => true])

    @if (($activeSkripsi ?? null) && ($activeSkripsi->current_phase === 'proposal') && (($activeSkripsi->proposal_review_status ?? null) === 'draft'))
        <div class="acss-modal" data-draft-edit-modal role="dialog" aria-modal="true" aria-labelledby="draft-edit-modal-title" hidden>
            <div class="acss-modal__backdrop" data-draft-edit-modal-close></div>
            <div class="acss-modal__dialog acss-modal__dialog--master" style="min-height: 480px; display: flex; flex-direction: column;">
                <div style="flex: 1 1 auto;">
                <div class="acss-modal__head">
                    <div>
                        <h3 class="acss-card-title" id="draft-edit-modal-title">Edit Proposal Draft</h3>
                    </div>
                    <button type="button" class="acss-modal__close" data-draft-edit-modal-close aria-label="Tutup">×</button>
                </div>
                <form class="acss-form-stack-tight" method="POST" action="{{ route('mahasiswa.skripsi.update', $activeSkripsi) }}" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1 1 auto; height: 100%;">
                    @csrf
                    @method('PUT')
                    @php
                        $currentProposalFile = optional($activeSkripsi->documentVersions()->where('phase', 'proposal')->orderByDesc('version_number')->first())->file_path;
                    @endphp
                    <div class="acss-master-form-shell" style="flex: 1 1 auto;">
                        <label class="form-field">
                            <span>Judul Penelitian</span>
                            <input type="text" name="title" value="{{ old('title', $activeSkripsi->title) }}" required>
                        </label>
                        <label class="form-field">
                            <span>Tipe</span>
                            <select name="type" required>
                                <option value="skripsi" @selected(old('type', $activeSkripsi->type) === 'skripsi')>Skripsi</option>
                                <option value="non_skripsi" @selected(old('type', $activeSkripsi->type) === 'non_skripsi')>Non Skripsi</option>
                            </select>
                        </label>
                        <label class="form-field form-field--upload">
                            <span>Dokumen Proposal</span>
                            <div class="acss-upload-field" data-upload-field>
                                <input type="file" name="proposal_file" accept=".pdf" id="proposal_file_edit" class="acss-hidden" data-upload-input>
                                <div class="acss-upload-field__row" style="display: flex; align-items: center; gap: 0.75rem; border: 1px solid #e2e8f0; border-radius: 8px; padding: 0.5rem; background: #fff;">
                                    <button type="button" class="button button--muted button--inline acss-upload-field__button" data-upload-trigger style="margin: 0; padding: 0.35rem 0.75rem; min-height: unset; font-size: 0.85rem; border-radius: 6px;">Pilih File</button>
                                    <span class="acss-upload-field__name" data-upload-name style="color: #64748b; font-size: 0.9rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $currentProposalFile ? basename($currentProposalFile) : 'Belum ada file dipilih' }}</span>
                                </div>
                                <small class="acss-muted block" style="margin-top: 0.5rem; display: block;">Proposal harus dalam format PDF.</small>
                            </div>
                        </label>
                    </div>
                    <div class="acss-page-card" style="margin-top: 1rem;">
                        <div class="acss-page-card__body" style="padding: 1rem 1.25rem;">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                <label class="form-field acss-field-tight md:w-1/3">
                                    <span>Status Pengajuan</span>
                                    <select name="save_mode" required>
                                        <option value="draft" @selected(old('save_mode', 'draft') === 'draft')>Draft</option>
                                        <option value="published" @selected(old('save_mode') === 'published')>Published</option>
                                    </select>
                                </label>
                                <div class="acss-form-actions">
                                    <button class="button button--inline" type="submit">Simpan Pengajuan</button>
                                    <button class="button button--muted button--inline" type="button" data-draft-edit-modal-close>Batal</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.querySelector('[data-draft-edit-modal]');
                if (!modal) return;

                document.querySelectorAll('[data-draft-edit-modal-open]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        modal.hidden = false;
                        document.body.classList.add('acss-modal-open');
                    });
                });

                document.querySelectorAll('[data-draft-edit-modal-close]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        modal.hidden = true;
                        document.body.classList.remove('acss-modal-open');
                    });
                });

                document.querySelectorAll('[data-upload-field]').forEach(function (field) {
                    const trigger = field.querySelector('[data-upload-trigger]');
                    const input = field.querySelector('[data-upload-input]');
                    const name = field.querySelector('[data-upload-name]');

                    if (!trigger || !input || !name) return;

                    trigger.addEventListener('click', function () {
                        input.click();
                    });

                    input.addEventListener('change', function () {
                        name.textContent = this.files && this.files[0] ? this.files[0].name : '{{ $currentProposalFile ? basename($currentProposalFile) : 'Belum ada file dipilih' }}';
                    });
                });
            });
        </script>
        @endpush
    @endif
@endsection
