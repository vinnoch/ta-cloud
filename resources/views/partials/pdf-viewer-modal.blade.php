@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.querySelector('[data-pdf-preview-modal]');
        const frame = document.querySelector('[data-pdf-preview-frame]');
        const name = document.querySelector('[data-pdf-preview-name]');

        if (!modal || !frame || !name) return;

        window.openPdfModal = function(url, title) {
            frame.src = url || '';
            name.textContent = title || 'Dokumen';
            modal.hidden = false;
            document.body.classList.add('acss-modal-open');
        };

        document.querySelectorAll('[data-pdf-preview-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                modal.hidden = true;
                frame.src = '';
                document.body.classList.remove('acss-modal-open');
            });
        });
    });
</script>
@endpush

<div class="acss-modal" data-pdf-preview-modal hidden>
    <div class="acss-modal__backdrop" data-pdf-preview-close></div>
    <div class="acss-modal__dialog acss-modal__dialog--pdf">
        <div class="acss-modal__head">
            <div>
                <h3 class="acss-card-title">Preview Dokumen</h3>
                <p class="acss-muted mt-1" data-pdf-preview-name>-</p>
            </div>
            <button type="button" class="acss-modal__close" data-pdf-preview-close aria-label="Tutup">×</button>
        </div>
        <div class="acss-pdf-preview">
            <iframe data-pdf-preview-frame title="Preview Dokumen PDF"></iframe>
        </div>
    </div>
</div>
