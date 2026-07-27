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
                $oldPeriodeIds = old('periode_ids');
                $selectedPeriodeIds = is_array($oldPeriodeIds)
                    ? array_map('strval', $oldPeriodeIds)
                    : (isset($template)
                        ? $template->periodes->pluck('id')->map(fn ($id) => (string) $id)->toArray()
                        : ($activePeriodeId ? [(string) $activePeriodeId] : []));
                $activePeriode = $activePeriode ?? null;
                $inactivePeriodes = collect($periodes)->reject(fn ($period) => $activePeriode && (int) $period->id === (int) $activePeriode->id);
            @endphp
            <div style="display:grid;grid-template-columns:minmax(0,1.6fr) minmax(280px,.9fr);gap:1rem;align-items:start;">
                <div class="acss-page-card" style="background:#fff;">
                    <div class="acss-page-card__body">
                        <div class="section-heading" style="margin-bottom:.75rem;">
                            <div>
                                <h4 class="acss-card-title" style="font-size:1rem;">Periode Tambahan</h4>
                                <p class="acss-muted">Bisa pilih lebih dari satu periode tidak aktif.</p>
                            </div>
                        </div>

                        <div style="display:grid;gap:.75rem;">
                            @forelse ($inactivePeriodes as $period)
                                @php
                                    $isSelected = in_array((string) $period->id, $selectedPeriodeIds, true);
                                    $isDisabled = $period->document_templates_count > 0 && ! $isSelected;
                                @endphp
                                <label style="display:flex;gap:.75rem;align-items:flex-start;padding:.85rem 1rem;border:1px solid #e2e8f0;border-radius:14px;background:{{ $isDisabled ? '#f8fafc' : ($isSelected ? '#eff6ff' : '#fff') }};opacity:{{ $isDisabled ? '.72' : '1' }};cursor:{{ $isDisabled ? 'not-allowed' : 'pointer' }};">
                                    <input type="checkbox" name="periode_ids[]" value="{{ $period->id }}" {{ $isSelected ? 'checked' : '' }} {{ $isDisabled ? 'disabled' : '' }} style="margin-top:.2rem;">
                                    <span style="display:grid;gap:.2rem;">
                                        <strong>{{ $period->name }}</strong>
                                        <span class="acss-muted">Kode: {{ $period->kode_periode ?: '-' }}</span>
                                        @if ($isDisabled)
                                            <span class="status-pill status-pill--draft" style="width:max-content;">Sudah dipakai template lain</span>
                                        @elseif ($isSelected)
                                            <span class="status-pill status-pill--published" style="width:max-content;">Terhubung</span>
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <div class="empty-state">Tidak ada periode tambahan yang tersedia.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="acss-page-card" style="background:#fff;">
                    <div class="acss-page-card__body">
                        <div class="section-heading" style="margin-bottom:.75rem;">
                            <div>
                                <h4 class="acss-card-title" style="font-size:1rem;">Periode Aktif Saat Ini</h4>
                                <p class="acss-muted">Periode utama yang sedang berjalan.</p>
                            </div>
                        </div>

                        @if ($activePeriode)
                            @php
                                $activeSelected = in_array((string) $activePeriode->id, $selectedPeriodeIds, true);
                                $activeDisabled = $activePeriode->document_templates_count > 0 && ! $activeSelected;
                            @endphp
                            <label style="display:flex;gap:.75rem;align-items:flex-start;padding:.9rem 1rem;border:1px solid #cbd5e1;border-radius:14px;background:{{ $activeSelected ? '#eff6ff' : '#fff7ed' }};opacity:{{ $activeDisabled ? '.72' : '1' }};cursor:{{ $activeDisabled ? 'not-allowed' : 'pointer' }};">
                                <input type="checkbox" name="periode_ids[]" value="{{ $activePeriode->id }}" {{ $activeSelected ? 'checked' : '' }} {{ $activeDisabled ? 'disabled' : '' }} style="margin-top:.2rem;">
                                <span style="display:grid;gap:.25rem;">
                                    <strong>{{ $activePeriode->name }}</strong>
                                    <span class="acss-muted">Kode: {{ $activePeriode->kode_periode ?: '-' }}</span>
                                    <div style="display:flex;flex-wrap:wrap;gap:.4rem;">
                                        <span class="status-pill status-pill--published">Aktif</span>
                                        @if ($activeDisabled)
                                            <span class="status-pill status-pill--draft">Sudah dipakai template lain</span>
                                        @elseif ($activeSelected)
                                            <span class="status-pill status-pill--published">Sudah terhubung</span>
                                        @else
                                            <span class="status-pill status-pill--locked">Belum terhubung</span>
                                        @endif
                                    </div>
                                </span>
                            </label>
                        @else
                            <div class="empty-state">Belum ada periode aktif.</div>
                        @endif
                    </div>
                </div>
            </div>

            @error('periode_ids') <small class="field-error" style="display:block;margin-top:.75rem;">{{ $message }}</small> @enderror
        </div>
    </div>
</div>
