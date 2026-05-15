@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const input = document.getElementById(@json($inputId));
                const label = document.getElementById(@json($labelId));
                const dropzone = document.querySelector('[data-import-dropzone]');
                const previewSection = document.getElementById('preview-section');
                const previewThead = document.getElementById('preview-thead');
                const previewTbody = document.getElementById('preview-tbody');
                const processBtn = document.getElementById('process-import-btn');
                const feedback = document.getElementById('import-live-feedback');
                const progressWrap = document.querySelector('[data-import-progress-wrap]');
                const progressBar = document.querySelector('[data-import-progress-bar]');
                let openingPicker = false;

                if (!input || !dropzone || !previewSection || !processBtn) return;

                function parseCSV(text) {
                    const lines = text.split(/\r?\n/).filter(l => l.trim() !== '');
                    if (lines.length < 1) return [];
                    return lines.map(line => {
                        const row = [];
                        let inQuotes = false;
                        let current = '';
                        for (let index = 0; index < line.length; index++) {
                            const char = line[index];
                            if (char === '"') inQuotes = !inQuotes;
                            else if (char === ',' && !inQuotes) {
                                row.push(current.trim());
                                current = '';
                            } else current += char;
                        }
                        row.push(current.trim());
                        return row;
                    });
                }

                function renderPreview(file) {
                    if (!file) return;
                    label.textContent = 'Terpilih: ' + file.name;
                    label.classList.remove('acss-hidden');

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const rows = parseCSV(e.target.result);
                        if (rows.length < 1) return;

                        const headers = rows[0];
                        const data = rows.slice(1, 11);

                        previewThead.innerHTML = `<tr class="table-shell__head">${headers.map(h => `<th class="px-4 py-2 text-left">${h}</th>`).join('')}</tr>`;
                        previewTbody.innerHTML = data.map(row => `<tr>${row.map(c => `<td class="px-4 py-2 border-t">${c}</td>`).join('')}</tr>`).join('');
                        previewSection.classList.remove('acss-hidden');
                        processBtn.classList.remove('acss-hidden');
                    };
                    reader.readAsText(file);
                }

                function openPicker() {
                    if (openingPicker) return;
                    openingPicker = true;
                    input.click();
                    setTimeout(() => { openingPicker = false; }, 250);
                }

                dropzone.addEventListener('click', function(e) {
                    if (e.target.closest('input')) return;
                    openPicker();
                });

                dropzone.addEventListener('keydown', function(event) {
                    if (event.key === 'Enter' || event.key === ' ') {
                        event.preventDefault();
                        openPicker();
                    }
                });

                input.addEventListener('change', function() {
                    renderPreview(this.files[0]);
                });

                ['dragenter', 'dragover'].forEach(type => {
                    dropzone.addEventListener(type, e => {
                        e.preventDefault();
                        dropzone.classList.add('is-dragover');
                    });
                });
                ['dragleave', 'drop'].forEach(type => {
                    dropzone.addEventListener(type, e => {
                        e.preventDefault();
                        dropzone.classList.remove('is-dragover');
                    });
                });
                dropzone.addEventListener('drop', e => {
                    e.preventDefault();
                    const files = e.dataTransfer.files;
                    if (!files.length) return;
                    input.files = files;
                    renderPreview(files[0]);
                });

                processBtn.addEventListener('click', function() {
                    const file = input.files[0];
                    if (!file) return;

                    processBtn.disabled = true;
                    processBtn.textContent = 'Memproses...';
                    progressWrap.classList.remove('acss-hidden');
                    progressBar.style.width = '10%';

                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('_token', @json(csrf_token()));

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', @json($storeRoute), true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('Accept', 'application/json');

                    xhr.upload.addEventListener('progress', event => {
                        if (event.lengthComputable) {
                            progressBar.style.width = Math.max(10, Math.round((event.loaded / event.total) * 100)) + '%';
                        }
                    });

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState !== 4) return;
                        if (xhr.status >= 200 && xhr.status < 300) {
                            progressBar.style.width = '100%';
                            const response = JSON.parse(xhr.responseText);
                            const summary = response.summary || {};
                            const errors = (summary.errors || []).map(err => `<div class="history-table__row"><small class="field-error acss-field-error-reset">${err}</small></div>`).join('');
                            feedback.innerHTML = `
                                <div class="notice notice--success">${response.message}</div>
                                <section class="card mb-4">
                                    <div class="section-heading"><div><h3>Hasil Import</h3></div></div>
                                    <div class="pill-row">
                                        <span class="pill">Dibuat: ${summary.created ?? 0}</span>
                                        <span class="pill">Diperbarui: ${summary.updated ?? 0}</span>
                                        <span class="pill">Gagal: ${summary.skipped ?? 0}</span>
                                    </div>
                                    ${errors ? `<div class="history-table acss-history-table-mt">${errors}</div>` : ''}
                                </section>`;
                            previewSection.classList.add('acss-hidden');
                            processBtn.classList.add('acss-hidden');
                            progressWrap.classList.add('acss-hidden');
                            processBtn.disabled = false;
                            processBtn.textContent = 'Proses Import';
                            return;
                        }

                        let message = 'Import gagal. Cek format CSV.';
                        try { message = JSON.parse(xhr.responseText).message || message; } catch (error) {}
                        feedback.innerHTML = `<div class="notice notice--danger">${message}</div>`;
                        processBtn.disabled = false;
                        processBtn.textContent = 'Proses Import';
                    };
                    xhr.send(formData);
                });
            });
        </script>
    @endpush
@endonce
