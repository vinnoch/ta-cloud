<div class="acss-meta-grid-tight">
    <label class="form-field acss-field-tight">
        <span>Nama Format Nilai</span>
        <input type="text" name="name" value="{{ old('name', $format->name ?? '') }}" placeholder="Contoh: Format Penilaian Skripsi SI 2024" required>
    </label>

    <label class="form-field acss-field-tight">
        <span>Program Studi</span>
        <input type="text" value="{{ $studyProgram?->name ?? $format->studyProgram?->name ?? '-' }}" disabled>
    </label>

    <label class="form-field acss-field-tight">
        <span>Jenis Format Penilaian</span>
        <select name="format_type" required>
            <option value="sidang_proposal" {{ old('format_type', $format->format_type ?? '') === 'sidang_proposal' ? 'selected' : '' }}>Proposal</option>
            <option value="sidang_skripsi" {{ old('format_type', $format->format_type ?? '') === 'sidang_skripsi' ? 'selected' : '' }}>Skripsi</option>
        </select>
    </label>

    <label class="form-field acss-field-tight">
        <span>Periode Akademik Terkait</span>
        <select name="periode_id" required>
            @foreach ($periodes as $period)
                <option value="{{ $period->id }}" {{ (string) old('periode_id', isset($format) ? optional($format->periodes->first())->id : ($activePeriodeId ?? '')) === (string) $period->id ? 'selected' : '' }}>
                    {{ $period->name }}
                </option>
            @endforeach
        </select>
    </label>
</div>
