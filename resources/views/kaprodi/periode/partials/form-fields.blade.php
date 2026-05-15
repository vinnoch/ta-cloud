<div class="acss-form-stack-tight">
    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h3 class="acss-card-title">Tahun Akademik</h3>
                <p class="acss-muted mt-1">Pilih tahun akademik dan semester periode.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <label class="form-field acss-field-tight">
                    <span>Tahun Akademik</span>
                    <select name="tahun_akademik_id" id="year_select" required>
                        <option value="">Pilih tahun akademik</option>
                        @foreach ($tahunAkademiks as $year)
                            <option value="{{ $year->id }}" data-year="{{ $year->tahun_awal }}" data-name="{{ $year->name }}" {{ old('tahun_akademik_id') == $year->id ? 'selected' : '' }}>
                                {{ $year->name }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="form-field acss-field-tight">
                    <span>Semester</span>
                    <select name="semester" id="semester_select" required>
                        <option value="">Pilih semester</option>
                        <option value="1" {{ old('semester') == '1' ? 'selected' : '' }}>1 (Ganjil)</option>
                        <option value="2" {{ old('semester') == '2' ? 'selected' : '' }}>2 (Genap)</option>
                    </select>
                </label>
            </div>
        </div>
    </section>

    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h3 class="acss-card-title">Dokumen SK</h3>
                <p class="acss-muted mt-1">Lengkapi dokumen SK dan masa berlaku periode.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <label class="form-field acss-field-tight">
                    <span>Nomor SK</span>
                    <input type="text" name="sk_nomor" value="{{ old('sk_nomor') }}" placeholder="Contoh: SK-FTI/2026/014">
                </label>

                <label class="form-field acss-field-tight">
                    <span>Link Dokumen SK</span>
                    <input type="url" name="sk_dokumen_url" value="{{ old('sk_dokumen_url') }}" placeholder="https://drive.google.com/file/d/.../view">
                </label>

                <label class="form-field acss-field-tight">
                    <span>Valid Sejak</span>
                    <input type="date" name="tgl_mulai" value="{{ old('tgl_mulai') }}" required>
                </label>

                <label class="form-field acss-field-tight">
                    <span>Valid Sampai</span>
                    <input type="date" name="tgl_selesai" value="{{ old('tgl_selesai') }}" required>
                </label>
            </div>
        </div>
    </section>
</div>