@extends('layouts.app')

@section('content')
@if (session('success'))
    <div class="notice notice--success">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div class="notice notice--danger">{{ session('error') }}</div>
@endif

@if ($errors->any())
    <div class="notice notice--danger">{{ $errors->first() }}</div>
@endif

@php
    $studentNameParts = preg_split('/\s+/', trim((string) ($skripsi->student->name ?? ''))) ?: [];
    $avatarInitials = collect($studentNameParts)
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

    <section class="card card--profile acss-skripsi-detail-topcompact">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ $avatarInitials !== '' ? $avatarInitials : 'P' }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ \Illuminate\Support\Str::title((string) ($skripsi->student->name ?? '-')) }}</h2>
                        <p>{{ $skripsi->student->nim ?? '-' }} • {{ $skripsi->periode?->name ?? ($skripsi->periode?->kode_periode ?? '-') }}</p>
                        <div class="acss-quote-title">{{ \Illuminate\Support\Str::title((string) ($skripsi->title ?: 'Proposal Skripsi')) }}</div>
                    </div>
                    <span class="status-pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->upper() }}</span>
                </div>
            </div>
        </div>
    </section>

    @if ($skripsi->proposal_review_status === 'pending')
        <div class="notice notice--warning ">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <strong>Konfirmasi Pengajuan Proposal</strong>
                    <p class="">Mahasiswa mengajukan proposal skripsi. Silakan tinjau dokumen sebelum memberikan persetujuan.</p>
                </div>
                <div class="flex gap-2">
                    <form method="POST" action="{{ route('kaprodi.skripsi.proposal.approve', $skripsi) }}">
                        @csrf
                        <button class="button button--success button--inline" type="submit"><span class="dosen-btn-icon">@include('partials.icons.check')</span><span>Setujui Proposal</span></button>
                    </form>
                    <button class="button button--danger button--inline" type="button" onclick="document.querySelector('[data-proposal-reject-modal]').hidden = false"><span class="dosen-btn-icon">@include('partials.icons.edit')</span><span>Revisi / Tolak</span></button>
                </div>
            </div>
        </div>
    @endif

    <div class="acss-detail-pair-grid">
        <section class="card">
            <div class="section-heading">
                <div>
                    <h3 class="acss-card-title">Riwayat Proposal</h3>
                </div>
            </div>
                <div class="table-shell table-shell--format-assigned table-shell--proposal-docs">
                <div class="table-shell__head table-shell__grid acss-table-cols-kaprodi-proposal-history">
                    <span>Tanggal</span>
                    <span>Status Upload</span>
                </div>
                @forelse ($skripsi->documentVersions->sortByDesc('version_number') as $document)
                    <div class="table-shell__row table-shell__grid acss-table-cols-kaprodi-proposal-history acss-hover-row-group">
                            <div class="table-shell__cell">
                                <strong>{{ $document->created_at?->format('d/m/Y') ?? '-' }}</strong>
                                <div class="text-[10px] acss-muted">{{ $document->created_at?->format('H:i') ?? '' }}</div>
                            </div>
                            <div class="table-shell__cell">
                                <div>{{ $document->version_number <= 1 ? 'Upload Baru' : 'Revisi ' . ($document->version_number - 1) }}</div>
                                <div class="acss-row-actions acss-row-actions--always">
                                    <a class="text-link acss-action-link" href="javascript:void(0)" onclick="openPdfModal('{{ route('documents.preview', $document) }}', 'Proposal v{{ $document->version_number }}')">@include('partials.icons.file')<span>Proposal</span></a>
                                </div>
                            </div>
                    </div>
                    @empty
                        <div class="empty-state">Belum ada dokumen proposal.</div>
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

    <div class="acss-modal" data-proposal-reject-modal hidden>
        <div class="acss-modal__backdrop" onclick="this.parentElement.hidden = true"></div>
        <div class="acss-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Catatan Penolakan / Revisi</h3>
                </div>
                <button type="button" class="acss-modal__close" onclick="this.closest('[data-proposal-reject-modal]').hidden = true">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.skripsi.proposal.reject', $skripsi) }}">
                @csrf
                <div class="acss-master-form-shell">
                    <div class="form-field">
                        <span>Catatan Penolakan / Revisi</span>
                        <textarea name="note" rows="4" placeholder="Berikan catatan perbaikan untuk mahasiswa..." required></textarea>
                    </div>
                </div>
                <div class="form-actions form-actions--inline">
                    <button class="button button--muted button--inline" type="button" onclick="this.closest('[data-proposal-reject-modal]').hidden = true">Batal</button>
                    <button class="button button--danger button--inline" type="submit">Kirim Penolakan</button>
                </div>
            </form>
        </div>
    </div>

    @include('partials.pdf-viewer-modal')

    <div class="acss-modal" data-reviewer-modal hidden>
        <div class="acss-modal__backdrop" data-reviewer-modal-close></div>
        <div class="acss-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Tambahkan Reviewer</h3>
                </div>
                <button type="button" class="acss-modal__close" data-reviewer-modal-close aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ $reviewerStoreUrl }}" id="assign-reviewer-form" data-store-url="{{ $reviewerStoreUrl }}" data-search-url="{{ $reviewerSearchUrl }}">
                @csrf
                <div class="acss-master-form-shell">
                    <label class="form-field">
                        <span>Dosen</span>
                        <div class="reviewer-search-container acss-relative">
                            <input type="text" class="reviewer-search" placeholder="Cari dosen..." autocomplete="off">
                            <input type="hidden" name="lecturer_id" required>
                            <ul class="reviewer-results acss-reviewer-results"></ul>
                        </div>
                    </label>
                    <label class="form-field">
                        <span>Peran Reviewer</span>
                        <select name="role_type" required>
                            <option value="pembimbing_1">Pembimbing 1</option>
                            <option value="pembimbing_2">Pembimbing 2</option>
                            <option value="penguji_1">Penguji 1</option>
                            <option value="penguji_2">Penguji 2</option>
                        </select>
                    </label>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-reviewer-modal-close>Batal</button>
                    <button class="button button--inline" type="submit">Tambahkan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (() => {
        const toggleModal = (modal, show) => {
            if (!modal) return;
            modal.hidden = !show;
            document.body.classList.toggle('acss-modal-open', show);
        };

        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-reviewer-modal-open]')) {
                toggleModal(document.querySelector('[data-reviewer-modal]'), true);
                return;
            }
            if (event.target.closest('[data-reviewer-modal-close]')) {
                toggleModal(document.querySelector('[data-reviewer-modal]'), false);
                return;
            }
        });

        const form = document.getElementById('assign-reviewer-form');
        const searchInput = form?.querySelector('.reviewer-search');
        const resultsList = form?.querySelector('.reviewer-results');
        const hiddenIdInput = form?.querySelector('input[name="lecturer_id"]');

        if (searchInput && resultsList) {
            let timeout = null;
            searchInput.addEventListener('input', (e) => {
                const query = e.target.value;
                clearTimeout(timeout);
                if (query.length < 2) {
                    resultsList.innerHTML = '';
                    return;
                }

                timeout = setTimeout(async () => {
                    const res = await fetch(`${form.dataset.searchUrl}?q=${encodeURIComponent(query)}`);
                    const data = await res.json();
                    resultsList.innerHTML = data.map(d => `<li data-id="${d.id}" data-name="${d.name}">${d.name} <small>${d.nidn_nip}</small></li>`).join('');
                }, 300);
            });

            resultsList.addEventListener('click', (e) => {
                const li = e.target.closest('li');
                if (li) {
                    searchInput.value = li.dataset.name;
                    hiddenIdInput.value = li.dataset.id;
                    resultsList.innerHTML = '';
                }
            });
        }
    })();
    </script>
@endsection
