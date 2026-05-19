<div class="acss-meta-grid-tight">
    <label class="form-field acss-field-tight">
        <span>Nama Dokumen Final</span>
        <input type="text" name="name" value="{{ old('name', old('nama', $template->name)) }}" placeholder="Contoh: Template Dokumen Final SI" required>
        @error('name') <small class="field-error">{{ $message }}</small> @enderror
        @error('nama') <small class="field-error">{{ $message }}</small> @enderror
    </label>

    <div></div>

    <div class="acss-page-card" style="grid-column: 1 / -1;">
        <div class="acss-page-card__body">
            <div class="section-heading">
                <div><h3 class="acss-card-title">Pilih Periode Terkait</h3></div>
            </div>
            @php
                $selectedPeriodeIds = (array) old('periode_ids', isset($template) && $template->relationLoaded('periodes')
                    ? $template->periodes->pluck('id')->map(fn($id) => (string) $id)->toArray()
                    : ($activePeriodeId ? [(string) $activePeriodeId] : []));
            @endphp
            <label class="form-field acss-field-tight">
                <span style="font-size: .88rem; font-weight: 600; color: #475569;">Tahan TAB untuk <i>multiple selection</i></span>
                <select name="periode_ids[]" multiple size="6" style="min-height: 12.5rem;">
                    @foreach ($periodes as $period)
                        @php $isDisabled = $period->document_templates_count > 0 && !in_array((string) $period->id, $selectedPeriodeIds, true); @endphp
                        <option value="{{ $period->id }}" {{ $isDisabled ? 'disabled' : '' }} {{ in_array((string) $period->id, $selectedPeriodeIds, true) ? 'selected' : '' }}>
                            {{ $period->name }}@if (!empty($period->kode_periode)) ({{ $period->kode_periode }})@endif
                        </option>
                    @endforeach
                </select>
            </label>
        </div>
    </div>
</div>
