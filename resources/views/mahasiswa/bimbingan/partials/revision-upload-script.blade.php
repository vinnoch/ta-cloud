@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const trashIcon = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M5.5 6.5h9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M8 3.75h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M7 6.5v8m6-8v8M6 6.5l.5 9a1 1 0 0 0 1 .944h4.999a1 1 0 0 0 1-.944L14 6.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
                const editIcon = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M3.75 13.75V16.25H6.25L14.5 8L12 5.5L3.75 13.75Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M11 6.5L13.5 9" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
                const fileIcon = '<svg viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M6.25 3.75h5.5L15 7v9.25H6.25V3.75Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M11.75 3.75V7H15" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><path d="M8 10.25h4M8 12.75h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg><span>Dokumen</span>';


                function bindDropzones() {
                    document.querySelectorAll('[data-import-dropzone]').forEach(function (dropzone) {
                        if (dropzone.dataset.bound === '1') return;
                        dropzone.dataset.bound = '1';

                        const input = dropzone.querySelector('input[type="file"]');
                        const fileName = dropzone.querySelector('[data-dropzone-file-name]');
                        if (!input) return;

                        dropzone.addEventListener('click', function (event) {
                            if (event.target.closest('label[for]') || event.target === input) return;
                            input.click();
                        });

                        dropzone.addEventListener('keydown', function (event) {
                            if (event.key === 'Enter' || event.key === ' ') {
                                event.preventDefault();
                                input.click();
                            }
                        });

                        ['dragenter', 'dragover'].forEach(function (type) {
                            dropzone.addEventListener(type, function (event) {
                                event.preventDefault();
                                dropzone.classList.add('border-[var(--primary)]');
                            });
                        });

                        ['dragleave', 'drop'].forEach(function (type) {
                            dropzone.addEventListener(type, function (event) {
                                event.preventDefault();
                                dropzone.classList.remove('border-[var(--primary)]');
                            });
                        });

                        dropzone.addEventListener('drop', function (event) {
                            const files = event.dataTransfer?.files;
                            if (!files || !files.length) return;
                            input.files = files;
                            if (fileName) {
                                fileName.textContent = files[0].name;
                                fileName.classList.remove('acss-hidden');
                            }
                            input.dispatchEvent(new Event('change', { bubbles: true }));
                        });

                        input.addEventListener('change', function () {
                            if (!fileName) return;
                            if (this.files && this.files.length) {
                                fileName.textContent = this.files[0].name;
                                fileName.classList.remove('acss-hidden');
                            } else {
                                fileName.textContent = '';
                                fileName.classList.add('acss-hidden');
                            }
                        });
                    });
                }

                function bindAutoUpload() {
                    document.querySelectorAll('[data-auto-upload]').forEach(function (input) {
                        if (input.dataset.bound === '1') return;
                        input.dataset.bound = '1';

                        input.addEventListener('change', function () {
                            const form = this.closest('[data-instant-upload-form]');
                            if (!this.files || !this.files.length || !form) return;

                            const data = new FormData(form);
                            const widget = form.closest('[data-upload-widget]');
                            const selectedName = this.files[0].name;

                            widget.innerHTML = `
                                <div class="acss-uploading-state">
                                    <div class="acss-uploading-state__label">Mengunggah ${selectedName}</div>
                                    <div class="acss-upload-progress is-uploading">
                                        <div class="acss-upload-progress__bar" data-live-progress-bar></div>
                                    </div>
                                    <div class="acss-uploading-state__percent" data-live-progress-text>0%</div>
                                </div>`;

                            const progressBar = widget.querySelector('[data-live-progress-bar]');
                            const progressText = widget.querySelector('[data-live-progress-text]');
                            const xhr = new XMLHttpRequest();
                            xhr.open('POST', form.action, true);
                            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                            xhr.setRequestHeader('Accept', 'application/json');

                            xhr.upload.addEventListener('progress', function (event) {
                                if (!event.lengthComputable) return;
                                const percent = Math.max(1, Math.round((event.loaded / event.total) * 100));
                                progressBar.style.width = percent + '%';
                                progressText.textContent = percent + '%';
                            });

                            xhr.onreadystatechange = function () {
                                if (xhr.readyState !== 4) return;

                                if (xhr.status >= 200 && xhr.status < 300) {
                                    progressBar.style.width = '100%';
                                    progressText.textContent = '100%';
                                    const progressLabel = widget.querySelector('.acss-uploading-state__label');
                                    if (progressLabel) {
                                        progressLabel.innerHTML = `<span class="text-link acss-action-link acss-preview-link-inline">${fileIcon}</span>`;
                                    }
                                    const payload = JSON.parse(xhr.responseText || '{}');
                                    setTimeout(function () {
                                        const replaceId = `replace_${Date.now()}_${Math.random().toString(36).slice(2)}`;
                                        const fileName = payload.filename || '';
                                        const displayName = fileName.length > 15 ? `${fileName.substring(0, 12)}...` : fileName;
                                        widget.innerHTML = `<div class="acss-revision-widget__done"><button class="text-link acss-action-link acss-preview-link-inline" type="button" data-preview-open data-preview-url="${payload.url}" data-preview-title="${fileName}" title="${fileName}">${fileIcon}</button><div class="acss-row-actions" style="margin-top:.35rem;"><form method="POST" action="${payload.upload_url || ''}" enctype="multipart/form-data" data-instant-upload-form class="acss-inline-form"><input type="hidden" name="_token" value="${document.querySelector('input[name="_token"]')?.value || ''}"><input type="hidden" name="_method" value="PUT"><input class="acss-file-input-hidden" id="${replaceId}" type="file" name="revision_file" accept=".pdf,.doc,.docx" required data-auto-upload><label class="text-link acss-action-link" for="${replaceId}">${editIcon}<span>Ganti</span></label></form><button class="acss-action-link acss-action-link--danger" type="button" data-remove-revision data-remove-url="${payload.remove_url}">${trashIcon}<span>Hapus</span></button></div></div>`;
                                        bindAutoUpload();
                                        bindPreviewButtons();
                                        bindRemoveButtons();
                                    }, 250);
                                    return;
                                }

                                let message = 'Upload gagal. Periksa format file dan ukuran maksimum 2 MB.';
                                try {
                                    const payload = JSON.parse(xhr.responseText || '{}');
                                    message = payload.message || payload.error || message;
                                } catch (error) {}
                                alert(message);
                                window.location.reload();
                            };

                            xhr.send(data);
                        });
                    });
                }

                function bindPreviewButtons() {
                    const modal = document.querySelector('[data-pdf-preview-modal]');
                    const frame = document.querySelector('[data-pdf-preview-frame]');
                    const name = document.querySelector('[data-pdf-preview-name]');
                    if (!modal || !frame || !name) return;

                    document.querySelectorAll('[data-preview-open]').forEach(function (button) {
                        if (button.dataset.bound === '1') return;
                        button.dataset.bound = '1';
                        button.addEventListener('click', function () {
                            frame.src = this.dataset.previewUrl || '';
                            name.textContent = this.dataset.previewTitle || 'Dokumen';
                            modal.hidden = false;
                            document.body.classList.add('acss-modal-open');
                        });
                    });

                    document.querySelectorAll('[data-pdf-preview-close]').forEach(function (button) {
                        if (button.dataset.bound === '1') return;
                        button.dataset.bound = '1';
                        button.addEventListener('click', function () {
                            modal.hidden = true;
                            frame.src = '';
                            document.body.classList.remove('acss-modal-open');
                        });
                    });
                }

                function bindRemoveButtons() {
                    document.querySelectorAll('[data-remove-revision]').forEach(function (button) {
                        if (button.dataset.bound === '1') return;
                        button.dataset.bound = '1';
                        button.addEventListener('click', async function () {
                            if (!await window.taConfirm('Anda yakin menghapus file ini? File akan dihapus permanen')) return;
                            const url = this.dataset.removeUrl;
                            if (!url) return;
                            fetch(url, {
                                method: 'DELETE',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]')?.value || ''
                                }
                            }).then(r => r.json()).then(() => {
                                window.location.reload();
                            }).catch(() => alert('Gagal menghapus file revisi.'));
                        });
                    });
                }

                bindDropzones();
                bindAutoUpload();
                bindPreviewButtons();
                bindRemoveButtons();
            });
        </script>
    @endpush
@endonce
