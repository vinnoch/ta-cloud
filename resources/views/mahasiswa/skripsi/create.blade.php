@extends('layouts.app')

@section('content')
    @php
        $taType = $taType ?? $selectedType ?? (request()->routeIs('mahasiswa.non-skripsi.*') ? 'non_skripsi' : 'skripsi');
        $taLabel = $taType === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi';
        $taRoutePrefix = $taType === 'non_skripsi' ? 'mahasiswa.non-skripsi' : 'mahasiswa.skripsi';
        $formRoute = !empty($isUnifiedRegistration) ? 'mahasiswa.pendaftaran.store' : $taRoutePrefix.'.store';
    @endphp
    <form class="acss-form-stack acss-form-stack--sm" method="POST" action="{{ route($formRoute) }}"
        enctype="multipart/form-data">
        @csrf
        <section class="acss-crud-card">
            <div class="acss-crud-head">
                <div>
                    <h1 class="acss-page-title">{{ $heading ?? 'Buat '.$taLabel.' Baru' }}</h1>
                </div>
            </div>

            <div class="acss-crud-body acss-form-stack acss-form-stack--sm">
                <label class="form-field">
                    <span>Judul Tugas Akhir</span>
                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Masukkan judul tugas akhir"
                        required>
                    @error('title')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </label>

                <div class="acss-form-split">
                    <label class="form-field">
                        <span>Tipe Tugas Akhir</span>
                        @if (!empty($isUnifiedRegistration))
                            <select name="type" required>
                                <option value="skripsi" @selected(old('type', $taType) === 'skripsi')>Skripsi</option>
                                <option value="non_skripsi" @selected(old('type', $taType) === 'non_skripsi')>Non-Skripsi</option>
                            </select>
                        @else
                            <input type="hidden" name="type" value="{{ $taType }}">
                            <input type="text" value="{{ $taLabel }}" readonly aria-readonly="true">
                        @endif
                        @error('type')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                    <label class="form-field">
                        <span>Periode</span>
                        <select name="periode_id" required>
                            <option value="" disabled selected>Pilih Periode</option>
                            @foreach ($periodes as $periode)
                                <option value="{{ $periode->id }}" @selected(old('periode_id') == $periode->id)>
                                    {{ $periode->name ?: $periode->kode_periode }}
                                </option>
                            @endforeach
                        </select>
                        @error('periode_id')
                            <small class="field-error">{{ $message }}</small>
                        @enderror
                    </label>
                </div>

                <div class="form-field">
                    <span>Dokumen Proposal</span>
                    <div class="acss-dropzone acss-dropzone--hint border border-dashed border-gray-300 rounded-md p-8 text-center cursor-pointer hover:border-[var(--primary)] transition-colors "
                        tabindex="0" data-dropzone-trigger="proposal_file">
                        <input type="file" name="proposal_file" accept="application/pdf" class="hidden"
                            id="proposal_file">
                        <p class="text-muted text-sm ">Klik atau drag n drop file proposal di sini</p>
                        <p class="text-sm font-medium text-[var(--primary)]  acss-hidden" data-file-label></p>
                    </div>
                    <small class="acss-muted  block">Format PDF. Ukuran maks 20 MB.</small>
                    @error('proposal_file')
                        <small class="field-error">{{ $message }}</small>
                    @enderror
                </div>

                @include('mahasiswa.skripsi.partials.type-understanding')

            </div>
        </section>

        <div class="acss-submission-footer">
            <div class="acss-status-label" aria-label="Status pengajuan">
                <span>Status</span>
                <strong>Draft</strong>
            </div>
            <div class="acss-form-actions">
                @if (empty($isUnifiedRegistration))
                    <a class="button button--muted button--inline" href="{{ route($taRoutePrefix.'.index') }}">Batal</a>
                @endif
                <button class="button button--muted button--inline" type="submit" name="save_mode" value="draft">Simpan Draft</button>
                <button class="button button--primary button--inline" type="submit" name="save_mode" value="published">Ajukan Proposal</button>
            </div>
        </div>
    </form>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const dropzone = document.querySelector('[data-dropzone-trigger="proposal_file"]');
                const input = document.getElementById('proposal_file');
                const label = document.querySelector('[data-file-label]');

                if (dropzone && input) {
                    dropzone.addEventListener('click', () => input.click());
                    input.addEventListener('change', function() {
                        if (this.files && this.files[0]) {
                            label.textContent = 'Selected: ' + this.files[0].name;
                            label.classList.remove('acss-hidden');
                        }
                    });
                }
            });
        </script>
    @endpush
@endsection
