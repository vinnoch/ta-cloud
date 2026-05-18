@extends('layouts.app')

@section('content')
    <div id="reviewer-feedback">
        @if (session('success'))
            <div class="notice notice--success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice--danger">{{ $errors->first() }}</div>
        @endif
        @if ($skripsi->current_phase === 'proposal' && $skripsi->proposal_review_status !== 'approved')
            <div class="notice notice--warning">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <strong>Approval Proposal</strong>
                        <div class="mt-1">Proposal mahasiswa ini menunggu persetujuan Anda untuk lanjut ke fase Sidang Proposal.</div>
                    </div>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('kaprodi.skripsi.proposal.approve', $skripsi) }}" onsubmit="return confirm('Setujui proposal ini?')">
                            @csrf
                            <button class="button button--small button--success" type="submit">Setujui Proposal</button>
                        </form>
                        <button class="button button--small button--danger" type="button" onclick="document.querySelector('[data-proposal-reject-modal]').hidden = false">Tolak / Revisi</button>
                    </div>
                </div>
            </div>
        @endif

        @php
            $pendingSidangRequest = $skripsi->sidangRequests->where('status', 'submitted')->where('role_type', '!=', 'mahasiswa')->first();
        @endphp

        @if ($pendingSidangRequest)
            <div class="notice notice--warning mt-2">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <strong>{{ $pendingSidangRequest->lecturer?->name ?? '-' }} ({{ str($pendingSidangRequest->role_type)->replace('_', ' ')->title() }}) telah mengajukan permohonan sidang untuk skripsi ini.</strong>
                    <div class="flex gap-2">
                        <form method="POST" action="{{ route('kaprodi.skripsi.sidang-request.approve', [$skripsi, $pendingSidangRequest]) }}" onsubmit="return confirm('Setujui permohonan sidang ini?')">
                            @csrf
                            <button class="button button--small button--success" type="submit">Setujui Sidang</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student->name, 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $skripsi->student->name }}</h2>
                        <p>{{ $skripsi->student->nim ?? '-' }} •
                            {{ $skripsi->periode?->name ?? ($skripsi->periode?->kode_periode ?? '-') }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                    </div>
                    <span class="status-pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                </div>
            </div>
        </div>

    </section>

    @include('partials.skripsi-phase-timeline', ['skripsiTimelineRecord' => $skripsi, 'timelineTitle' => 'Timeline Fase Skripsi'])


    <div class="acss-stack-sections">
        @if ($skripsi->current_phase === 'review_dokumen_final')
            <section class="card card--notice mt-4 acss-final-review-card">
                <div class="section-heading">
                    <div>
                        <h3>Validasi Dokumen Final</h3>
                        <p class="acss-muted mt-1">Periksa dokumen final yang sudah dikirim mahasiswa sebelum menyelesaikan skripsi.</p>
                    </div>
                </div>
                <div class="acss-action-group p-4">
                    <p>Seluruh reviewer telah menyetujui dokumen final. Lakukan validasi akhir untuk menyatakan skripsi selesai.</p>

                    <div class="acss-final-documents mt-4">
                        @forelse ($finalReviewDocuments as $document)
                            <div class="acss-final-documents__item">
                                <div>
                                    <strong>{{ str($document->phase)->replace('_', ' ')->title() }}</strong>
                                    <small>{{ $document->created_at?->format('d/m/Y H:i') ?? '-' }} · {{ $document->uploader?->name ?? 'Mahasiswa' }}</small>
                                </div>
                                <button type="button" class="text-link acss-action-link" onclick="openPdfModal('{{ route('kaprodi.skripsi.documents.download', [$skripsi, $document]) }}', '{{ str($document->phase)->replace('_', ' ')->title() }}')">@include('partials.icons.eye')<span>Dokumen PDF</span></button>
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

                    <div class="flex gap-2 mt-4">
                        <form method="POST" action="{{ route('kaprodi.skripsi.final-review.approve', $skripsi) }}" onsubmit="return confirm('Validasi dokumen final dan selesaikan skripsi?')">
                            @csrf
                            <button class="button button--small button--success" type="submit">Validasi & Selesaikan Skripsi</button>
                        </form>
                    </div>
                </div>
            </section>
        @endif

        <section class="card">
            <div class="section-heading">
                <div>
                    <h3>Reviewer</h3>
                </div>
            </div>
            <div id="reviewer-list">{!! $reviewerTableHtml !!}</div>
            <div class="acss-link-gap-top">
                <button type="button" class="acss-link-subtle" data-reviewer-modal-open>Tambahkan Reviewer</button>
            </div>
        </section>

        <section class="card">
            <div class="section-heading">
                <div>
                    <h3>Histori Bimbingan Terakhir</h3>
                </div>
            </div>
            <div class="table-shell">
                @if (count($latestBimbingans ?? []) > 0)
                    <div class="table-shell__head table-shell__grid" style="--table-cols:repeat(4,minmax(0,1fr));">
                        <span>Tanggal</span>
                        <span>Fase</span>
                        <span>Reviewer</span>
                        <span>Catatan</span>
                    </div>
                @endif
                @forelse (($latestBimbingans ?? []) as $bimbingan)
                    <div class="table-shell__row table-shell__grid" style="--table-cols:repeat(4,minmax(0,1fr));">
                        <div class="table-shell__cell"><strong>{{ $bimbingan->meeting_date?->format('d/m/Y') ?? '-' }}</strong></div>
                        <div class="table-shell__cell">{{ str($bimbingan->phase)->replace('_', ' ')->title() }}</div>
                        <div class="table-shell__cell">{{ $bimbingan->reviewer?->name ?? '-' }}</div>
                        <div class="table-shell__cell">
                            {{ \Illuminate\Support\Str::limit($bimbingan->lecturer_notes ?: '-', 80) }}
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada histori bimbingan.</div>
                @endforelse
            </div>
            <div class="acss-link-gap-top">
                <a class="acss-link-subtle" href="{{ route('kaprodi.skripsi.bimbingan', $skripsi) }}">Lihat Semua Histori
                    Bimbingan</a>
                <span class="acss-action-separator">|</span>
                <a class="acss-link-subtle" href="{{ route('kaprodi.skripsi.logbook', $skripsi) }}">Download Logbook
                    CSV</a>
            </div>
        </section>

        <section class="card">
            <div class="section-heading">
                <div>
                    <h3>Permohonan Sidang</h3>
                </div>
            </div>
            <div class="table-shell">
                @if (count($sidangRequests ?? []) > 0)
                    <div class="table-shell__head table-shell__grid" style="--table-cols:repeat(3,minmax(0,1fr));">
                        <span>Tanggal</span>
                        <span>Reviewer</span>
                        <span>Aksi</span>
                    </div>
                @endif
                @forelse (($sidangRequests ?? []) as $sidangRequest)
                    <div class="table-shell__row table-shell__grid" style="--table-cols:repeat(3,minmax(0,1fr));">
                        <div class="table-shell__cell">{{ $sidangRequest->submitted_at?->format('d/m/Y') ?? '-' }}</div>
                        <div class="table-shell__cell">{{ $sidangRequest->lecturer?->name ?? '-' }}</div>
                        <div class="table-shell__cell">
                            @if ($sidangRequest->status !== 'approved')
                                <form method="POST"
                                    action="{{ route('kaprodi.skripsi.sidang-request.approve', [$skripsi, $sidangRequest]) }}"
                                    onsubmit="return confirm('Setujui permohonan sidang ini?')">
                                    @csrf
                                    <button class="button button--small button--success" type="submit">Approve</button>
                                </form>
                            @else
                                <span class="pill">Disetujui</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada permohonan sidang.</div>
                @endforelse
            </div>
        </section>

        <section class="card">
            <div class="section-heading">
                <div>
                    <h3>Penilaian Sidang Skripsi</h3>
                    <p class="acss-muted mt-1">Pantau dosen yang sudah dan belum mengirim nilai.</p>
                </div>
            </div>
            <div class="acss-grading-progress-grid">
                <div class="acss-grading-progress-card acss-grading-progress-card--stacked">
                    <div class="acss-grading-progress-head">
                        <span class="acss-grading-progress-label">Sudah Final</span>
                        <strong class="acss-grading-progress-count">{{ $gradingProgress['submitted_count'] }}/{{ $gradingProgress['expected_count'] }}</strong>
                    </div>
                    <div class="acss-grading-progress-lists mt-3">
                        <div>
                            <div class="acss-grading-pill-wrap">
                                @forelse ($gradingProgress['submitted_reviewers'] as $reviewer)
                                    <span class="pill pill--blue">{{ $reviewer['name'] }} • {{ $reviewer['role'] }}</span>
                                @empty
                                    <span class="acss-muted">Belum ada dosen yang mengirim nilai.</span>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                <div class="acss-grading-progress-card acss-grading-progress-card--stacked">
                    <div class="acss-grading-progress-head">
                        <span class="acss-grading-progress-label">Belum Final</span>
                    </div>
                    <div class="acss-grading-progress-lists mt-3">
                        <div>
                            <div class="acss-grading-pill-wrap">
                                @forelse ($gradingProgress['pending_reviewers'] as $reviewer)
                                    <span class="pill">{{ $reviewer['name'] }} • {{ $reviewer['role'] }}</span>
                                @empty
                                    @if (($gradingProgress['expected_count'] ?? 0) === 0)
                                        <span class="acss-muted">Belum ada dosen penilai yang ditetapkan.</span>
                                    @else
                                        <span class="acss-muted">Semua dosen penilai sudah mengirim nilai.</span>
                                    @endif
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="acss-link-gap-top">
                <a class="acss-link-subtle" href="{{ route('kaprodi.nilai.index', ['skripsi_id' => $skripsi->id]) }}">Lihat Semua Nilai</a>
            </div>
        </section>


        <section class="card">
            <div class="section-heading">
                <div>
                    <h3>Artikel Jurnal Terpublikasi</h3>
                </div>
            </div>
            @if ($journalArticleUrl)
                <div class="acss-journal-link">
                    <a href="{{ $journalArticleUrl }}" target="_blank" rel="noopener noreferrer"
                        class="acss-journal-link__anchor">
                        <svg class="acss-journal-link__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
                            <polyline points="15 3 21 3 21 9" />
                            <line x1="10" y1="14" x2="21" y2="3" />
                        </svg>
                        <span>{{ $journalArticleUrl }}</span>
                    </a>
                </div>
            @else
                <div class="empty-state">Belum ada link artikel jurnal.</div>
            @endif
        </section>
    </div>

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
                        if (!await window.taConfirm('Unassign reviewer ini?')) {
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
                        showMessage(data.message || 'Reviewer berhasil di-unassign.');
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
@endsection

    <div class="acss-modal" data-proposal-reject-modal hidden>
        <div class="acss-modal__backdrop" onclick="this.parentElement.hidden = true"></div>
        <div class="acss-modal__dialog">
            <div class="acss-modal__content">
                <div class="acss-modal__header">
                    <h2 class="acss-modal__title">Tolak / Revisi Proposal</h2>
                </div>
                <form method="POST" action="{{ route('kaprodi.skripsi.proposal.reject', $skripsi) }}">
                    @csrf
                    <div class="acss-modal__body">
                        <div class="form-field">
                            <label>Catatan Revisi</label>
                            <textarea name="note" rows="4" placeholder="Berikan catatan perbaikan untuk mahasiswa..." required></textarea>
                        </div>
                    </div>
                    <div class="acss-modal__footer">
                        <button class="button button--muted" type="button" onclick="this.closest('[data-proposal-reject-modal]').hidden = true">Batal</button>
                        <button class="button button--danger" type="submit">Kirim Penolakan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
