<div class="acss-meta-grid-tight">
    <label class="form-field acss-field-tight">
        <span>Nama Format Nilai</span>
        <input type="text" name="name" value="{{ old('name', $format->name ?? '') }}" placeholder="Contoh: Format Penilaian Skripsi SI 2024" required>
    </label>

    <label class="form-field acss-field-tight">
        <span>Nilai Sidang</span>
        <select name="format_type" required>
            <option value="sidang_proposal" {{ old('format_type', $format->format_type ?? '') === 'sidang_proposal' ? 'selected' : '' }}>Proposal</option>
            <option value="sidang_skripsi" {{ old('format_type', $format->format_type ?? '') === 'sidang_skripsi' ? 'selected' : '' }}>Skripsi</option>
            <option value="sidang_non_skripsi" {{ old('format_type', $format->format_type ?? '') === 'sidang_non_skripsi' ? 'selected' : '' }}>Non-Skripsi</option>
        </select>
    </label>

    <div class="acss-page-card" style="grid-column: 1 / -1;">
        <div class="acss-page-card__body">
            <div class="section-heading">
                <div><h3 class="acss-card-title">Pilih Periode Terkait</h3></div>
            </div>
            @php
                $currentFormatId = $format->id ?? null;
                $selectedPeriodeIds = (array) old('periode_ids', isset($format) ? $format->periodes->pluck('id')->map(fn($id) => (string) $id)->toArray() : ($activePeriodeId ? [(string) $activePeriodeId] : []));
            @endphp
            <label class="form-field acss-field-tight">
                <span style="font-size: .88rem; font-weight: 600; color: #475569;">Tahan TAB untuk <i>multiple selection</i></span>
                <select name="periode_ids[]" multiple size="6" style="min-height: 12.5rem;">
                    @foreach ($periodes as $period)
                        @php $isDisabled = $period->formats_count > 0 && !in_array((string) $period->id, $selectedPeriodeIds, true); @endphp
                        <option value="{{ $period->id }}" 
                            {{ $isDisabled ? 'disabled' : '' }} 
                            {{ in_array((string) $period->id, $selectedPeriodeIds, true) ? 'selected' : '' }}>
                            {{ $period->name }}@if (!empty($period->kode_periode)) ({{ $period->kode_periode }})@endif
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>
</div>
