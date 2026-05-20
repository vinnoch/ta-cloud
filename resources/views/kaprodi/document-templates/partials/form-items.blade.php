@php
    $itemRows = old('items', isset($template) && $template->relationLoaded('items')
        ? $template->items->sortBy('sort_order')->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'type' => $item->type ?? 'file',
            'is_required' => (bool) $item->is_required,
        ])->values()->all()
        : [
            ['name' => 'Dokumen Skripsi', 'type' => 'file', 'is_required' => true],
            ['name' => 'Dataset', 'type' => 'file', 'is_required' => true],
            ['name' => 'Abstrak', 'type' => 'link', 'is_required' => true],
        ]);
    $shouldShowRows = old('items') !== null || (isset($template) && $template->relationLoaded('items') && $template->items->isNotEmpty());
    $itemCount = $shouldShowRows ? max(count($itemRows), 1) : 0;
@endphp

<div id="document-builder-root" class="space-y-4">
    <div class="max-w-sm">
        <label class="form-field acss-field-tight">
            <span>Jumlah Item Dokumen</span>
            <input
                id="item-count-select"
                type="number"
                min="0"
                max="10"
                step="1"
                inputmode="numeric"
                data-item-count
                value="{{ $itemCount }}"
                placeholder="0"
            >
        </label>
    </div>

    <div id="item-rows" class="space-y-4 {{ $shouldShowRows ? '' : 'hidden' }}">
        @if ($shouldShowRows)
            @foreach ($itemRows as $index => $item)
                <div class="acss-page-card document-item-row" data-item-row>
                <div class="acss-page-card__body" style="display:grid;gap:1rem;grid-template-columns:minmax(0,1fr) 180px 180px;">
                    <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] ?? '' }}">
                    <label class="form-field acss-field-tight">
                        <span>Nama Dokumen</span>
                        <input type="text" name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" placeholder="Contoh: Dokumen Skripsi" required>
                    </label>
                    <label class="form-field acss-field-tight">
                        <span>Tipe</span>
                        <select name="items[{{ $index }}][type]">
                            <option value="file" {{ ($item['type'] ?? 'file') === 'file' ? 'selected' : '' }}>File Upload</option>
                            <option value="link" {{ ($item['type'] ?? 'file') === 'link' ? 'selected' : '' }}>Google Drive Link</option>
                        </select>
                    </label>
                    <label class="form-field acss-field-tight">
                        <span>Sifat</span>
                        <select name="items[{{ $index }}][is_required]">
                                <option value="1" {{ ($item['is_required'] ?? false) ? 'selected' : '' }}>Wajib</option>
                                <option value="0" {{ ! ($item['is_required'] ?? false) ? 'selected' : '' }}>Opsional</option>
                            </select>
                        </label>
                    </div>
                </div>
            @endforeach
        @endif
    </div>

    @error('items') <small class="field-error">{{ $message }}</small> @enderror
</div>

<template id="item-row-template">
    <div class="acss-page-card document-item-row" data-item-row>
        <div class="acss-page-card__body" style="display:grid;gap:1rem;grid-template-columns:minmax(0,1fr) 180px 180px;">
            <input type="hidden" data-name-template="items[__INDEX__][id]" value="">
            <label class="form-field acss-field-tight">
                <span>Nama Dokumen</span>
                <input type="text" data-name-template="items[__INDEX__][name]" placeholder="Contoh: Dokumen Skripsi" required>
            </label>
            <label class="form-field acss-field-tight">
                <span>Tipe</span>
                <select data-name-template="items[__INDEX__][type]">
                    <option value="file" selected>File Upload</option>
                    <option value="link">Google Drive Link</option>
                </select>
            </label>
            <label class="form-field acss-field-tight">
                <span>Sifat</span>
                <select data-name-template="items[__INDEX__][is_required]">
                    <option value="1" selected>Wajib</option>
                    <option value="0">Opsional</option>
                </select>
            </label>
        </div>
    </div>
</template>

<script>
(() => {
    const root = document.getElementById('document-builder-root');
    if (!root) return;

    const countSelect = root.querySelector('[data-item-count]');
    const rowsContainer = document.getElementById('item-rows');
    const template = document.getElementById('item-row-template');

    const buildRow = (index) => {
        const fragment = template.content.cloneNode(true);
        fragment.querySelectorAll('[data-name-template]').forEach((element) => {
            element.setAttribute('name', element.dataset.nameTemplate.replace('__INDEX__', index));
        });
        return fragment;
    };

    const syncRows = () => {
        const target = Number(countSelect.value || 0);
        const current = rowsContainer.querySelectorAll('[data-item-row]').length;

        if (!target || target < 1) {
            rowsContainer.innerHTML = '';
            rowsContainer.classList.add('hidden');
            return;
        }

        rowsContainer.classList.remove('hidden');

        if (target > current) {
            for (let index = current; index < target; index += 1) {
                rowsContainer.appendChild(buildRow(index));
            }
        } else if (target < current) {
            Array.from(rowsContainer.querySelectorAll('[data-item-row]')).slice(target).forEach((row) => row.remove());
        }
    };

    countSelect.addEventListener('input', () => {
        const rawValue = parseInt(countSelect.value || '0', 10);
        const value = Number.isNaN(rawValue) ? 0 : Math.max(0, Math.min(10, rawValue));
        countSelect.value = value > 0 ? value : 0;
        syncRows();
    });
})();
</script>
