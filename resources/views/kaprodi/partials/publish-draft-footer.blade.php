@php
    $isDraft = $currentStatus === 'draft';
@endphp

<div class="acss-submission-footer">
    <div class="acss-status-label" aria-label="Status saat ini">
        <span>Status</span>
        <strong>{{ $isDraft ? 'Draft' : 'Published' }}</strong>
    </div>
    <div class="acss-form-actions acss-form-actions--end">
        <a class="button button--muted button--inline" href="{{ $cancelUrl }}">Batal</a>
        @if ($isDraft)
            <button class="button button--muted button--inline" type="submit" name="status" value="draft">Simpan Draft</button>
            <button class="button button--primary button--inline" type="submit" name="status" value="published">Publish</button>
        @else
            <button class="button button--primary button--inline" type="submit" name="status" value="published">Simpan Perubahan</button>
        @endif
    </div>
</div>
