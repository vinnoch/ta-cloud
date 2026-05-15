@extends('layouts.app')

@section('content')
    <section class="acss-page-card mb-4">
        <div class="acss-page-card__body">
            <div>
                <h1 class="acss-page-title">Dashboard Mahasiswa</h1>
                <p class="acss-muted mt-1">Ringkasan progres skripsi dan tahapan bimbingan.</p>
            </div>
        </div>
    </section>

    @if (!empty($needsProposalUpload) && !empty($proposalUploadUrl))
        <section class="acss-section-card mb-4">
            <div class="acss-section-card__body">
                <div>
                    <strong class="text-red-600 font-bold uppercase tracking-wide">Anda belum mengirimkan Proposal.</strong>
                    <p class="acss-muted mt-1">Upload sekarang agar proses review bisa dimulai.</p>
                </div>
                <button type="button" class="button button--inline" data-proposal-modal-trigger>Upload Proposal</button>
            </div>
        </section>
    @endif

    <section class="acss-stat-grid">
        @foreach ($stats ?? [] as $stat)
            <article class="acss-stat-card">
                <span class="acss-stat-label">{{ $stat['label'] ?? '' }}</span>
                <strong class="acss-stat-value">{{ $stat['value'] ?? '-' }}</strong>
                @if (!empty($stat['hint']))
                    <small class="acss-stat-hint">{{ $stat['hint'] }}</small>
                @endif
            </article>
        @endforeach
    </section>

    <section class="acss-section-card mb-4">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Skripsi Saya</h3>
                <p class="acss-muted mt-1">Topik dan progres tugas akhir yang sedang berjalan.</p>
            </div>
        </div>
        <div class="acss-section-card__body">
            @if (!empty($activeSkripsi))
                <div class="table-shell">
                    <div class="table-shell__head table-shell__grid acss-table-cols-mhs-dashboard-history">
                        <span>Topik</span>
                        <span>Jenis</span>
                        <span>Pembimbing</span>
                        <span>Bimbingan</span>
                    </div>
                    <div class="table-shell__row table-shell__grid acss-table-cols-mhs-dashboard-history acss-hover-row-group">
                        <div class="table-shell__cell">
                            <strong>{{ $activeSkripsi['title'] ?? '-' }}</strong>
                            <div class="acss-row-actions">
                                <a class="text-link acss-action-link"
                                    href="{{ $activeSkripsi['detail_url'] ?? route('mahasiswa.skripsi.index') }}">@include('partials.icons.eye')<span>Bimbingan</span></a>
                                @if (!empty($activeSkripsiRecord))
                                    <span class="acss-action-separator">|</span>
                                    <a class="text-link acss-action-link"
                                        href="{{ route('mahasiswa.skripsi.nilai.index', $activeSkripsiRecord) }}">@include('partials.icons.eye')<span>Nilai</span></a>
                                @endif
                            </div>
                        </div>
                        <div class="table-shell__cell"><span
                                class="pill">{{ strtoupper($activeSkripsi['type'] ?? '-') }}</span></div>
                        <div class="table-shell__cell">{{ $activeSkripsi['pembimbing'] ?? '-' }}</div>
                        <div class="table-shell__cell"><strong>{{ $activeSkripsi['bimbingan_count'] ?? 0 }} kali</strong>
                        </div>
                    </div>
                </div>
            @else
                <div class="empty-state">
                    Belum ada tugas akhir aktif.
                    <div class="form-actions form-actions--inline mt-4">
                        <a class="button button--inline" href="{{ route('mahasiswa.skripsi.create', ['type' => 'skripsi']) }}">Buat Skripsi
                            Baru</a>
                        <a class="button button--muted button--inline"
                            href="{{ route('mahasiswa.skripsi.create', ['type' => 'non_skripsi']) }}">Buat Non-Skripsi Baru</a>
                    </div>
                </div>
            @endif
        </div>
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Histori Bimbingan Terakhir</h3>
            </div>
        </div>
        <div class="acss-section-card__body">
            <div class="history-table">
                <div class="history-table__head history-table__head--four">
                    <span>Tanggal</span>
                    <span>Fase</span>
                    <span>Dosen</span>
                    <span>Catatan</span>
                </div>
                @forelse (($latestBimbingans ?? []) as $bimbingan)
                    <div class="history-table__row history-table__row--four acss-hover-row-group">
                        <div>
                            <strong>{{ $bimbingan->meeting_date?->format('d/m/Y') ?? '-' }}</strong>
                            @if (!empty($activeSkripsi))
                                <div class="acss-row-actions">
                                    <a class="text-link acss-action-link"
                                        href="{{ route('mahasiswa.skripsi.bimbingan.index', $activeSkripsiRecord) . '#bimbingan-' . $bimbingan->id }}">@include('partials.icons.eye')<span>Bimbingan</span></a>
                                    @if ($bimbingan->has_revision_file)
                                        <button type="button" class="text-link acss-action-link" data-preview-open
                                            data-preview-url="{{ $bimbingan->revision_file_url }}"
                                            data-preview-title="{{ basename($bimbingan->revision_file_url) }}">@include('partials.icons.eye')<span>File
                                                Dokumen</span></button>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div>{{ str($bimbingan->phase)->replace('_', ' ')->title() }}</div>
                        <div>{{ $bimbingan->reviewer?->name ?? '-' }}</div>
                        <div>{{ \Illuminate\Support\Str::limit($bimbingan->lecturer_notes ?: '-', 80) }}</div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada histori bimbingan.</div>
                @endforelse
            </div>
            @if (!empty($activeSkripsiRecord))
                <div class="acss-link-gap-top">
                    <a class="acss-link-subtle"
                        href="{{ route('mahasiswa.skripsi.bimbingan.index', $activeSkripsiRecord) }}">Lihat Semua Histori
                        Bimbingan</a>
                </div>
            @endif
        </div>
    </section>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const modal = document.querySelector('[data-pdf-preview-modal]');
                const frame = document.querySelector('[data-pdf-preview-frame]');
                const name = document.querySelector('[data-pdf-preview-name]');
                if (modal && frame && name) {
                    document.querySelectorAll('[data-preview-open]').forEach(function(button) {
                        if (button.dataset.bound === '1') return;
                        button.dataset.bound = '1';
                        button.addEventListener('click', function() {
                            frame.src = this.dataset.previewUrl || '';
                            name.textContent = this.dataset.previewTitle || 'Dokumen';
                            modal.hidden = false;
                            document.body.classList.add('acss-modal-open');
                        });
                    });
                    document.querySelectorAll('[data-pdf-preview-close]').forEach(function(button) {
                        if (button.dataset.bound === '1') return;
                        button.dataset.bound = '1';
                        button.addEventListener('click', function() {
                            modal.hidden = true;
                            frame.src = '';
                            document.body.classList.remove('acss-modal-open');
                        });
                    });
                }
        const proposalModal = document.querySelector('[data-proposal-upload-modal]');
        const proposalTrigger = document.querySelector('[data-proposal-modal-trigger]');
        const proposalCloseButtons = document.querySelectorAll('[data-proposal-modal-close]');
        const proposalForm = document.querySelector('[data-proposal-upload-form]');
        const proposalDropzone = document.querySelector('[data-proposal-dropzone]');
        const proposalInput = document.querySelector('[data-proposal-upload-input]');
        const proposalProgressWrap = document.querySelector('[data-proposal-progress-wrap]');
        const proposalProgressBar = document.querySelector('[data-proposal-progress-bar]');
        const proposalPercent = document.querySelector('[data-proposal-percent]');
        const proposalLabel = document.querySelector('[data-proposal-file-label]');
        const autoOpenProposal = {{ !empty($openProposalUpload) ? 'true' : 'false' }};

        function openProposalModal() {
            if (!proposalModal) return;
            proposalModal.hidden = false;
            document.body.classList.add('acss-modal-open');
        }

        function closeProposalModal() {
            if (!proposalModal) return;
            proposalModal.hidden = true;
            document.body.classList.remove('acss-modal-open');
        }

        if (proposalTrigger) {
            proposalTrigger.addEventListener('click', openProposalModal);
        }

        proposalCloseButtons.forEach(function (button) {
            button.addEventListener('click', closeProposalModal);
        });

        if (autoOpenProposal) {
            openProposalModal();
        }

        function uploadProposalFile() {
            if (!proposalForm || !proposalInput || !proposalInput.files || !proposalInput.files[0]) return;
            proposalProgressWrap?.classList.add('is-uploading');
            if (proposalProgressBar) proposalProgressBar.style.width = '8%';
            if (proposalPercent) proposalPercent.textContent = '8%';
            const data = new FormData(proposalForm);
            const xhr = new XMLHttpRequest();
            xhr.open('POST', proposalForm.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');
            xhr.upload.addEventListener('progress', function (event) {
                if (!event.lengthComputable) return;
                const pct = Math.max(10, Math.round((event.loaded / event.total) * 100));
                if (proposalProgressBar) proposalProgressBar.style.width = pct + '%';
                if (proposalPercent) proposalPercent.textContent = pct + '%';
            });
            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4) return;
                if (xhr.status >= 200 && xhr.status < 300) {
                    window.location.reload();
                } else {
                    let message = 'Upload gagal. Pastikan file PDF dan ukuran maksimum 20 MB.';
                    try { const payload = JSON.parse(xhr.responseText || '{}'); message = payload.message || payload.error || message; } catch (error) {}
                    alert(message);
                    proposalProgressWrap?.classList.remove('is-uploading');
                    if (proposalProgressBar) proposalProgressBar.style.width = '0%';
                    if (proposalPercent) proposalPercent.textContent = '0%';
                }
            };
            xhr.send(data);
        }

        if (proposalDropzone && proposalInput) {
            proposalDropzone.addEventListener('click', function (event) {
                if (event.target !== proposalInput) proposalInput.click();
            });
            proposalDropzone.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    proposalInput.click();
                }
            });
            proposalInput.addEventListener('change', function () {
                if (!this.files || !this.files[0]) return;
                if (proposalLabel) {
                    proposalLabel.textContent = 'Selected: ' + this.files[0].name;
                    proposalLabel.classList.remove('acss-hidden');
                }
                uploadProposalFile();
            });
        }

            });
        </script>
    @endpush

@if (!empty($needsProposalUpload) && !empty($proposalUploadUrl))
    <div class="acss-modal" data-proposal-upload-modal hidden>
        <div class="acss-modal__backdrop" data-proposal-modal-close></div>
        <div class="acss-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Upload Proposal</h3>
                    <p class="acss-muted mt-1">Upload proposal awal untuk memulai proses review.</p>
                </div>
                <button type="button" class="acss-modal__close" data-proposal-modal-close aria-label="Tutup">×</button>
            </div>
            <div class="p-6">
                <form action="{{ $proposalUploadUrl }}" method="POST" enctype="multipart/form-data" data-proposal-upload-form>
                    @csrf
                    <input type="hidden" name="phase" value="proposal">
                    <div class="acss-dropzone acss-dropzone--hint border border-dashed border-gray-300 rounded-md p-8 text-center cursor-pointer hover:border-[var(--primary)] transition-colors" tabindex="0" data-proposal-dropzone>
                        <input type="file" name="file" accept="application/pdf" class="hidden" id="proposal_upload_file" data-proposal-upload-input>
                        <p class="text-muted text-sm mb-2">Drag &amp; drop file here, or click to select</p>
                        <p class="text-sm font-medium text-[var(--primary)] mt-2 acss-hidden" data-proposal-file-label></p>
                    </div>
                    <div class="acss-upload-progress mt-4" data-proposal-progress-wrap>
                        <div class="acss-uploading-state__label">Mengunggah proposal... <span data-proposal-percent>0%</span></div>
                        <div class="acss-upload-progress__bar-container bg-gray-100 h-2 rounded-full overflow-hidden">
                            <div class="acss-upload-progress__bar h-full bg-[#446553]" data-proposal-progress-bar></div>
                        </div>
                    </div>
                    <small class="acss-muted mt-3 block">Maksimum 20 MB. Format file: PDF.</small>
                </form>
            </div>
        </div>
    </div>
@endif


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
