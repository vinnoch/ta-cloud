@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="notice notice--danger">{{ session('error') }}</div>
    @endif

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ $identity['avatar'] }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $identity['name'] }}</h2>
                    </div>
                    <span class="status-pill">DOSEN</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-heading">
            <div>
                <h3 class="acss-card-title">Informasi Dosen</h3>
            </div>
        </div>
        <div class="acss-crud-body" style="padding-left:0; padding-right:0;">
            <div class="acss-info-grid" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; padding: 0;">
                <div class="acss-info-item">
                    <span style="font-size: 1rem; font-weight: 600;"><span class="u-upper">NIDN / NIP</span></span>
                    <strong>{{ $dosen->nidn_nip ?: '-' }}</strong>
                </div>
                <div class="acss-info-item">
                    <span style="font-size: 1rem; font-weight: 600;">Program Studi</span>
                    <strong>Sistem Informasi</strong>
                </div>
                <div class="acss-info-item">
                    <span style="font-size: 1rem; font-weight: 600;">Email</span>
                    <strong>{{ $dosen->email ?? '-' }}</strong>
                </div>
            </div>
        </div>
        <div class="form-actions form-actions--inline mt-4">
            @if (! $dosen->trashed())
                <button type="button" class="button button--muted button--inline" data-dosen-edit-modal-open>Edit Dosen</button>
                @if ($hasRelatedRecords)
                    <form method="POST" action="{{ route('kaprodi.dosen.archive', $dosen) }}" onsubmit="return confirm('Arsipkan dosen ini?')">
                        @csrf
                        <button class="button button--danger button--inline" type="submit">Arsipkan Dosen</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('kaprodi.dosen.destroy', $dosen->id) }}" onsubmit="return confirm('Hapus permanen dosen ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger button--inline" type="submit">Hapus Dosen</button>
                    </form>
                @endif
            @else
                <form method="POST" action="{{ route('kaprodi.dosen.restore', $dosen->id) }}">
                    @csrf
                    <button class="button button--muted button--inline" type="submit">Pulihkan Dosen</button>
                </form>
            @endif
        </div>
    </section>

    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h3 class="acss-card-title">Mahasiswa Bimbingan</h3>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="filter-bar" method="GET" action="{{ route('kaprodi.dosen.show', $dosen) }}">
                <label class="form-field acss-search-field">
                    <span>Cari Mahasiswa</span>
                    <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari nama, NIM, atau judul skripsi">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Filter Status</span>
                    <select name="status" onchange="this.form.submit()">
                        <option value="" {{ ($statusFilter ?? '') === '' ? 'selected' : '' }}>Semua Status</option>
                        <option value="sidang_proposal" {{ ($statusFilter ?? '') === 'sidang_proposal' ? 'selected' : '' }}>Sidang Proposal</option>
                        <option value="bimbingan_skripsi" {{ ($statusFilter ?? '') === 'bimbingan_skripsi' ? 'selected' : '' }}>Bimbingan Skripsi</option>
                        <option value="revisi_sidang_skripsi" {{ ($statusFilter ?? '') === 'revisi_sidang_skripsi' ? 'selected' : '' }}>Revisi Sidang Skripsi</option>
                        <option value="sidang_skripsi" {{ ($statusFilter ?? '') === 'sidang_skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                        <option value="review_dokumen_final" {{ ($statusFilter ?? '') === 'review_dokumen_final' ? 'selected' : '' }}>Review Dokumen Final</option>
                    </select>
                </label>
            </form>
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid acss-table-cols-kaprodi-dosen-bimb">
                    <span>Mahasiswa</span>
                    <span>Judul Skripsi</span>
                    <span>Bimbingan</span>
                    <span>Status</span>
                </div>

                @forelse ($mahasiswaBimbingan as $item)
                    <div class="table-shell__row table-shell__grid acss-table-cols-kaprodi-dosen-bimb acss-hover-row-group">
                        <div class="table-shell__cell">
                            <strong>{{ $item['student']->name }}</strong>
                            <small>{{ $item['student']->nim }}</small>
                            @if (!empty($item['student']) && !empty($item['student']->id))
                                <div class="acss-row-actions">
                                    <a class="text-link acss-action-link" href="{{ route('kaprodi.mahasiswa.show', $item['student']) }}">@include('partials.icons.eye')<span>Detail</span></a>
                                    @if (!empty($item['skripsi_url']))
                                        <span class="acss-action-separator">|</span>
                                        <a class="text-link acss-action-link" href="{{ $item['skripsi_url'] }}">@include('partials.icons.eye')<span>Skripsi</span></a>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="table-shell__cell table-shell__cell--title">
                            {{ $item['skripsi_topic'] ?: '-' }}
                            @if (!empty($item['skripsi']))
                                <div class="acss-row-actions">
                                    <a class="text-link acss-action-link" href="{{ route('kaprodi.skripsi.show', $item['skripsi']) }}">@include('partials.icons.eye')<span>Monitoring</span></a>
                                </div>
                            @endif
                        </div>
                        <div class="table-shell__cell">
                            <strong>{{ $item['guidance_count'] }}</strong> <span class="u-lower">bimbingan</span>
                            @if (!empty($item['bimbingan_url']))
                                <div class="acss-row-actions">
                                    <a class="text-link acss-action-link" href="{{ $item['bimbingan_url'] }}">@include('partials.icons.eye')<span>Histori</span></a>
                                </div>
                            @endif
                        </div>
                        <div class="table-shell__cell"><span class="pill">{{ strtoupper($item['status']) }}</span></div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada mahasiswa bimbingan.</div>
                @endforelse
            </div>
        </div>
    </section>

    <div class="acss-modal" data-dosen-edit-modal hidden>
        <div class="acss-modal__backdrop" data-dosen-edit-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Dosen</h3>
                </div>
                <button type="button" class="acss-modal__close" data-dosen-edit-modal-close aria-label="Tutup">×</button>
            </div>
            <form id="dosen-edit-form" class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.dosen.update', $dosen) }}">
                <div class="acss-master-form-shell">
                <div class="notice notice--danger"><span class="u-upper">NIDN / NIP</span> harus unik. Jika sudah dipakai dosen lain, perubahan tidak bisa disimpan.</div>
                @csrf
                @method('PUT')
                @include('kaprodi.dosen.partials.form-fields', [
                    'dosen' => $dosen,
                    'passwordRequired' => false,
                ])
                </div>
                <div class="pill-row">
                    <span class="pill">Login enabled</span>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-dosen-edit-modal-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

<script>
(() => {
    const toggleModal = (modal, show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };

    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-dosen-edit-modal-open]')) {
            toggleModal(document.querySelector('[data-dosen-edit-modal]'), true);
            return;
        }

        if (event.target.closest('[data-dosen-edit-modal-close]')) {
            const modal = event.target.closest('.acss-modal');
            toggleModal(modal, false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.acss-modal:not([hidden])').forEach((modal) => toggleModal(modal, false));
    });
})();
</script>
@endsection
