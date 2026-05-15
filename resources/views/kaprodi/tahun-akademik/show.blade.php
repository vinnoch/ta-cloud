@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ $identity['avatar'] }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $identity['name'] }}</h2>
                        <p>{{ $identity['period'] }} • Tahun Akademik</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="acss-inline-actions form-actions form-actions--inline">
            <button type="button" class="button button--muted button--inline" data-ta-show-edit-open>Edit Tahun Akademik</button>
            @if ($hasPeriods)
                <form method="POST" action="{{ route('kaprodi.tahun-akademik.archive', $tahunAkademik) }}" onsubmit="return confirm('Arsipkan tahun akademik ini?')">
                    @csrf
                    <button class="button button--danger button--inline" type="submit">Arsipkan Tahun Akademik</button>
                </form>
            @else
                <form method="POST" action="{{ route('kaprodi.tahun-akademik.destroy', $tahunAkademik) }}" onsubmit="return confirm('Hapus tahun akademik ini?')">
                    @csrf
                    @method('DELETE')
                    <button class="button button--danger button--inline" type="submit">Hapus Tahun Akademik</button>
                </form>
            @endif
            
        </div>
    </section>

    <section class="acss-crud-card mt-4">
        <div class="acss-crud-head">
            <div>
                <h3 class="acss-card-title">Periode Terkait</h3>
            </div>
        </div>

        <div class="acss-crud-body">
            <div class="table-shell">
                <div class="table-shell__head table-shell__grid acss-table-cols-ta-periode">
                    <span>Kode periode</span>
                    <span>Semester</span>
                    <span>Status</span>
                </div>
                @forelse ($tahunAkademik->periodes as $period)
                    <div class="table-shell__row table-shell__grid acss-table-cols-ta-periode acss-hover-row-group">
                        <div class="table-shell__cell">
                            <strong>{{ $period->kode_periode }}</strong>
                            <div class="acss-row-actions">
                                <a class="text-link acss-action-link" href="{{ route('kaprodi.periode.show', $period) }}">@include('partials.icons.eye')<span>Detail</span></a>
                            </div>
                        </div>
                        <div class="table-shell__cell">Semester {{ (int) $period->semester === 1 ? 'Ganjil' : 'Genap' }}</div>
                        <div class="table-shell__cell"><span class="pill">{{ strtoupper($period->status) }}</span></div>
                    </div>
                @empty
                    <div class="empty-state">Belum ada periode untuk tahun akademik ini.</div>
                @endforelse
            </div>
        </div>
    </section>


    <div class="acss-modal" data-ta-show-edit-modal hidden>
        <div class="acss-modal__backdrop" data-ta-show-edit-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Tahun Akademik</h3>
                </div>
                <button type="button" class="acss-modal__close" data-ta-show-edit-close aria-label="Tutup">×</button>
            </div>
            <form class="acss-form-stack-tight" method="POST" action="{{ route('kaprodi.tahun-akademik.update', $tahunAkademik) }}">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell">
                <div class="acss-grid-two acss-grid-full acss-meta-grid-tight">
                    <label class="form-field acss-field-tight">
                        <span>Tahun Awal</span>
                        <input type="number" name="tahun_awal" value="{{ old('tahun_awal', $tahunAkademik->tahun_awal) }}" placeholder="Contoh: 2026" required>
                    </label>
                    <label class="form-field acss-field-tight">
                        <span>Tahun Akhir</span>
                        <input type="number" name="tahun_akhir" value="{{ old('tahun_akhir', $tahunAkademik->tahun_akhir) }}" placeholder="Contoh: 2027" required>
                    </label>
                </div>
                </div>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-ta-show-edit-close>Batal</button>
                    <button class="button button--inline" type="submit">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

<script>
(() => {
    const modal = document.querySelector('[data-ta-show-edit-modal]');
    const toggleModal = (show) => {
        if (!modal) return;
        modal.hidden = !show;
        document.body.classList.toggle('overflow-hidden', show);
    };
    document.addEventListener('click', (event) => {
        if (event.target.closest('[data-ta-show-edit-open]')) toggleModal(true);
        if (event.target.closest('[data-ta-show-edit-close]')) toggleModal(false);
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') toggleModal(false);
    });
})();
</script>
@endsection
