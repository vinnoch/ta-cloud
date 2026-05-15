{{-- LOCKED UI PATTERN: Import page layout and preview flow are shared. Reuse this partial; do not redesign inline per page. --}}
<div id="import-live-feedback" class="space-y-4 mb-4"></div>

<div class="acss-form-split items-start">
    <div class="acss-stack-block">
        <section class="card">
            <div class="section-heading">
                <div>
                    <h1 class="acss-page-title">{{ $title }}</h1>
                    <p class="acss-muted">{{ $description }}</p>
                    <small class="inline-block mt-2 p-1 px-3 border border-[var(--primary)] rounded text-[var(--primary)] font-medium bg-[var(--primary-soft)]"><a class="no-underline" href="{{ asset('import-templates/' . $templateName) }}" download>Download {{ $templateName }}</a></small>
                </div>
            </div>

            <div class="form-grid">
                <div class="form-field">
                    <span>Upload File CSV</span>
                    <div class="acss-dropzone mb-4" tabindex="0" data-import-dropzone>
                        <input type="file" name="file" accept=".csv,.txt" class="hidden" id="{{ $inputId }}">
                        <div class="acss-dropzone__inner">
                            <div class="acss-dropzone__icon"><svg viewBox="0 0 64 64" fill="none" aria-hidden="true"><path d="M20 46h24c6.627 0 12-5.149 12-11.5 0-5.643-4.242-10.336-9.888-11.325C44.537 16.754 39.058 12 32.5 12c-7.216 0-13.194 5.755-13.48 13.01C12.645 25.53 8 30.477 8 36.5 8 41.747 13.373 46 20 46Z" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/><path d="M32 36V24m0 0-5 5m5-5 5 5" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                            <p class="acss-dropzone__title">Tarik &amp; lepas file Anda atau <span class="acss-dropzone__browse">Browse</span></p>
                            <p class="acss-dropzone__filename acss-hidden" id="{{ $labelId }}"></p>
                            <p class="acss-dropzone__meta">Format didukung: CSV.</p>
                        </div>
                    </div>
                    @error('file')<small class="field-error">{{ $message }}</small>@enderror
                </div>

                <div class="pill-row acss-grid-full">
                    @foreach ($requiredColumns as $column)
                        <span class="pill">Wajib: {{ $column }}</span>
                    @endforeach
                    @foreach ($optionalColumns as $column)
                        <span class="pill">Opsional: {{ $column }}</span>
                    @endforeach
                </div>

                <div class="acss-upload-progress acss-hidden" data-import-progress-wrap>
                    <div class="acss-upload-progress__bar" data-import-progress-bar></div>
                </div>

                <div class="form-actions mt-4 flex justify-between items-center">
                    
                    <button type="button" id="process-import-btn" class="button button--inline acss-hidden">Proses Import</button>
                </div>
            </div>
        </section>
    </div>

    <div class="acss-stack-block acss-hidden" id="preview-section">
        <section class="card h-full">
            <div class="section-heading">
                <div>
                    <h3 class="acss-card-title">Pratinjau Data</h3>
                    <p class="acss-muted">Cek kecocokan kolom sebelum mengirim.</p>
                </div>
            </div>
            <div class="acss-crud-body overflow-x-auto">
                <table class="table-shell w-full text-sm">
                    <thead id="preview-thead"></thead>
                    <tbody id="preview-tbody"></tbody>
                </table>
            </div>
        </section>
    </div>
</div>

@include('kaprodi.import.partials.import-script', [
    'inputId' => $inputId,
    'labelId' => $labelId,
    'storeRoute' => $storeRoute,
])
