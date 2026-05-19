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
                    <span class="status-pill">{{ $identity['status'] }}</span>
                </div>
            </div>
        </div>
    </section>

    <section class="card">
        <div class="section-heading">
            <div>
                <h3 class="acss-card-title">Informasi Mahasiswa</h3>
            </div>
        </div>
        <div class="acss-crud-body" style="padding-left:0; padding-right:0;">
            <div class="acss-info-grid acss-info-grid--mahasiswa" style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; padding: 0;">
                <div class="acss-info-item">
                    <span style="font-size: 1rem; font-weight: 600;"><span class="u-upper">NIM</span></span>
                    <strong>{{ $mahasiswa->nim ?? '-' }}</strong>
                </div>
                <div class="acss-info-item">
                    <span style="font-size: 1rem; font-weight: 600;">Program Studi</span>
                    <strong>Sistem Informasi</strong>
                </div>
                <div class="acss-info-item">
                    <span style="font-size: 1rem; font-weight: 600;">Email</span>
                    <strong>{{ $mahasiswa->email ?? '-' }}</strong>
                </div>
            </div>
        </div>
        <div class="form-actions form-actions--inline mt-4">
            @if (! $mahasiswa->trashed())
                <button type="button" class="button button--muted button--inline" data-mahasiswa-edit-modal-open>Edit Mahasiswa</button>
                @if ($hasRunningSkripsi)
                    <form method="POST" action="{{ route('kaprodi.mahasiswa.archive', $mahasiswa) }}" onsubmit="return confirm('Arsipkan mahasiswa ini?')">
                        @csrf
                        <button class="button button--danger button--inline" type="submit">Arsipkan Mahasiswa</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('kaprodi.mahasiswa.destroy', $mahasiswa->id) }}" onsubmit="return confirm('Hapus permanen mahasiswa ini?')">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger button--inline" type="submit">Hapus Mahasiswa</button>
                    </form>
                @endif
            @else
                <form method="POST" action="{{ route('kaprodi.mahasiswa.restore', $mahasiswa->id) }}">
                    @csrf
                    <button class="button button--muted button--inline" type="submit">Pulihkan Mahasiswa</button>
                </form>
            @endif
        </div>
    </section>

    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h3 class="acss-card-title">Skripsi Aktif</h3>
            </div>
        </div>

        <div class="acss-crud-body">
            <div class="table-shell table-shell--mahasiswa-skripsi{{ $skripsiData['record'] ? '' : ' table-shell--empty' }}">
                @if ($skripsiData['record'])
                <div class="table-shell__head table-shell__grid table-shell__grid--mahasiswa-skripsi">
                    <span>Judul Skripsi</span>
                    <span>Pembimbing</span>
                    <span>Bimbingan Terakhir</span>
                    <span>Fase</span>
                </div>
                <div class="table-shell__row table-shell__grid table-shell__grid--mahasiswa-skripsi acss-hover-row-group">
                    <div class="table-shell__cell table-shell__cell--title">
                        <strong>{{ $skripsiData['topic'] }}</strong>
                        <div class="acss-row-actions">
                                <a href="{{ route('kaprodi.skripsi.show', $skripsiData['record']) }}" class="text-link acss-action-link">@include('partials.icons.eye')<span>Skripsi</span></a>
                                <span class="acss-action-separator">|</span>
                                @include('kaprodi.skripsi.partials.status-modal', [
                                    'modalId' => 'mahasiswa-skripsi-status-' . $skripsiData['record']->id,
                                    'skripsiItem' => $skripsiData['record'],
                                    'statusUpdateUrl' => route('kaprodi.skripsi.status.update', $skripsiData['record']),
                                    'triggerLabel' => 'Edit Fase',
                                    'triggerClass' => 'text-link acss-action-link',
                                ])
                        </div>
                    </div>
                    <div class="table-shell__cell">
                        <strong>{{ $skripsiData['advisor'] }}</strong>
                    </div>
                    <div class="table-shell__cell">
                        <strong>{{ (int) ($skripsiData['guidance_count'] ?? 0) > 0 ? $skripsiData['guidance_count'] . ' bimbingan' : '-' }}</strong>
                        <div class="acss-cell-subtext">{{ $skripsiData['last_guidance'] }}</div>
                    </div>
                    <div class="table-shell__cell">
                        <span class="badge badge--success">{{ $skripsiData['status'] }}</span>
                    </div>
                </div>
                @else
                    <div class="empty-state">Belum ada skripsi aktif.</div>
                @endif
            </div>
        </div>
    </section>


    <div class="acss-modal" data-mahasiswa-edit-modal hidden>
        <div class="acss-modal__backdrop" data-mahasiswa-edit-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Mahasiswa</h3>
                </div>
                <button type="button" class="acss-modal__close" data-mahasiswa-edit-modal-close aria-label="Tutup">×</button>
            </div>
            <form id="mahasiswa-edit-form" class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.mahasiswa.update', $mahasiswa) }}">
                <div class="acss-master-form-shell">
                <div class="notice notice--danger"><span class="u-upper">NIM</span> harus unik. Jika NIM sudah dipakai mahasiswa lain, perubahan tidak bisa disimpan.</div>
                @csrf
                @method('PUT')
                @include('kaprodi.mahasiswa.partials.form-fields', [
                    'mahasiswa' => $mahasiswa,
                    'passwordRequired' => false,
                ])
                </div>
                <div class="pill-row">
                    <span class="pill">Role otomatis: MAHASISWA</span>
                    <span class="pill">Login enabled</span>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-mahasiswa-edit-modal-close>Batal</button>
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
        const openButton = event.target.closest('[data-status-modal-open]');
        if (openButton) {
            const modal = document.querySelector(`[data-status-modal="${openButton.dataset.statusModalOpen}"]`);
            toggleModal(modal, true);
            const phaseSelect = modal?.querySelector('[data-status-phase-select]');
            if (phaseSelect && openButton.dataset.statusCurrentPhase) phaseSelect.value = openButton.dataset.statusCurrentPhase;
            return;
        }

        if (event.target.closest('[data-mahasiswa-edit-modal-open]')) {
            toggleModal(document.querySelector('[data-mahasiswa-edit-modal]'), true);
            return;
        }

        if (event.target.closest('[data-status-modal-close]') || event.target.closest('[data-mahasiswa-edit-modal-close]')) {
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
