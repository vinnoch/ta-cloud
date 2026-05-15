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

    <div class="acss-modal" data-status-modal="{{ $modalId }}" hidden>
        <div class="acss-modal__backdrop" data-status-modal-close></div>
        <div class="acss-modal__dialog">
            <div class="acss-modal__head">
                <div>
                    <h3 class="acss-card-title">Edit Fase Skripsi</h3>
                    <p class="acss-muted">Perbarui fase skripsi mahasiswa.</p>
                </div>
                <button type="button" class="acss-modal__close" data-status-modal-close aria-label="Tutup">×</button>
            </div>
            <form method="POST" action="{{ $statusUpdateUrl }}" class="acss-form-stack-tight">
                @csrf
                @method('PUT')
                <label class="form-field">
                    <span>Fase</span>
                    <select name="current_phase" data-status-phase-select required>
                        @foreach ($phaseOptions as $value => $label)
                            <option value="{{ $value }}" {{ $currentPhaseValue === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="form-actions form-actions--inline">
                    <button type="button" class="button button--muted button--inline" data-status-modal-close>Batal</button>
                    <button type="submit" class="button button--inline">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
@endif
