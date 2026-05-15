<div class="acss-grid-two acss-grid-full acss-meta-grid-tight">
                    <label class="form-field acss-field-tight">
                        <span>Tahun Awal</span>
                        <input type="number" name="tahun_awal" value="{{ old('tahun_awal') }}" placeholder="Contoh: 2026" required>
                        @error('tahun_awal')<small class="field-error">{{ $message }}</small>@enderror
                    </label>

                    <label class="form-field acss-field-tight">
                        <span>Tahun Akhir</span>
                        <input type="number" name="tahun_akhir" value="{{ old('tahun_akhir') }}" placeholder="Contoh: 2027" required>
                        @error('tahun_akhir')<small class="field-error">{{ $message }}</small>@enderror
                    </label>
                </div>