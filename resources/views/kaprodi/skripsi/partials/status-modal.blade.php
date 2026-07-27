@php
    $modalId = $modalId ?? 'skripsi-status-modal';
    $skripsiItem = $skripsiItem ?? null;
    $statusUpdateUrl = $statusUpdateUrl ?? null;
    $triggerLabel = $triggerLabel ?? 'Edit Fase';
    $triggerClass = $triggerClass ?? 'text-link';
    $rawPhaseValue = old('current_phase', $skripsiItem?->current_phase);
    $currentPhaseValue = match (str((string) $rawPhaseValue)->lower()->replace(['-', ' '], '_')->toString()) {
        'proposal', 'pengajuan_proposal' => 'proposal',
        'sidang_proposal' => 'sidang_proposal',
        'bimbingan', 'bimbingan_skripsi' => 'bimbingan_skripsi',
        'sidang_skripsi', 'pasca_sidang' => 'sidang_skripsi',
        'revisi_sidang_skripsi' => 'revisi_sidang_skripsi',
        'review_dokumen_final', 'approval_pending' => 'review_dokumen_final',
        'skripsi_selesai', 'final', 'approved', 'selesai' => 'skripsi_selesai',
        default => 'proposal',
    };
    $phaseOptions = [
        'proposal' => 'Proposal',
        'sidang_proposal' => 'Sidang Proposal',
        'bimbingan_skripsi' => 'Bimbingan Skripsi',
        'sidang_skripsi' => 'Sidang Skripsi',
        'revisi_sidang_skripsi' => 'Revisi Sidang Skripsi',
        'review_dokumen_final' => 'Review Dokumen Final',
        'skripsi_selesai' => 'Skripsi Selesai',
    ];
@endphp

@if ($skripsiItem && $statusUpdateUrl)
    <button
        type="button"
        class="{{ $triggerClass }}"
        data-status-modal-open="{{ $modalId }}"
        data-status-current-phase="{{ $currentPhaseValue }}"
    >@if (str_contains($triggerClass, 'acss-action-link')) @include('partials.icons.edit') @endif<span>{{ $triggerLabel }}</span></button>

    <div class="acss-modal" data-status-modal="{{ $modalId }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" hidden>
        <div class="acss-modal__backdrop" data-status-modal-close></div>
        <div class="acss-modal__dialog acss-modal__dialog--master">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title" id="{{ $modalId }}-title">Edit Fase Skripsi</h3>
                    <p class="acss-muted">Perbarui fase skripsi mahasiswa.</p>
                </div>
                <button type="button" class="acss-modal__close" data-status-modal-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ $statusUpdateUrl }}" class="acss-form-stack-tight acss-status-modal-form" style="display:grid; gap:1rem; padding:1.35rem 1.5rem 1.5rem;">
                @csrf
                @method('PUT')
                <div class="acss-master-form-shell acss-status-modal-shell" style="display:grid; gap:1rem; width:100%;">
                    <label class="form-field acss-field-tight acss-status-modal-field" style="display:grid; gap:.45rem; width:100%;">
                        <span>Fase</span>
                        <select name="current_phase" data-status-phase-select required style="width:100%;">
                            @foreach ($phaseOptions as $value => $label)
                                <option value="{{ $value }}" {{ $currentPhaseValue === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <div class="form-actions form-actions--inline acss-status-modal-actions" style="display:flex; justify-content:flex-end; gap:.75rem; width:100%; padding-top:.55rem;">
                    <button type="button" class="button button--muted button--inline" data-status-modal-close>Batal</button>
                    <button type="submit" class="button button--inline">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endif
