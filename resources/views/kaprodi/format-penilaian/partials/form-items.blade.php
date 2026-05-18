@php
    $itemRows = old('items', isset($format) && $format->relationLoaded('items')
        ? $format->items->sortBy('sort_order')->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->name,
            'code' => $item->code,
            'weight' => $item->weight,
        ])->values()->all()
        : match (old('format_type', $format->format_type ?? 'sidang_proposal')) {
            'sidang_skripsi' => [
                ['name' => 'Penulisan', 'code' => 'penulisan', 'weight' => 25],
                ['name' => 'Presentasi', 'code' => 'presentasi', 'weight' => 25],
                ['name' => 'Penguasaan Materi', 'code' => 'penguasaan_materi', 'weight' => 50],
            ],
            default => [
                ['name' => 'Penulisan', 'code' => 'penulisan', 'weight' => 30],
                ['name' => 'Presentasi', 'code' => 'presentasi', 'weight' => 20],
                ['name' => 'Penguasaan Masalah', 'code' => 'penguasaan_masalah', 'weight' => 50],
            ],
        });
    $itemCount = count($itemRows);
    $shouldShowRows = old('items') !== null || (isset($format) && $format->relationLoaded('items') && $format->items->isNotEmpty());
@endphp

<div id="phase-builder-root" class="space-y-4">
    <div class=" max-w-sm">
        <label class="form-field acss-field-tight">
            <span>Jumlah Item Penilaian</span>
            <select id="item-count-select" data-item-count>
                <option value="">Pilih jumlah item</option>
                @for ($count = 1; $count <= 10; $count++)
                    <option value="{{ $count }}" {{ $shouldShowRows && $itemCount === $count ? 'selected' : '' }}>{{ $count }} Item</option>
                @endfor
            </select>
        </label>
        <div id="phase-empty-state" class="acss-muted text-sm  {{ $shouldShowRows ? 'hidden' : '' }}">Pilih jumlah item penilaian untuk menampilkan field.</div>
    </div>

    <div id="phase-rows-container" class="{{ $shouldShowRows ? '' : 'hidden' }}">
        @if ($shouldShowRows)
            @foreach ($itemRows as $index => $item)
                <div class="acss-phase-block grid grid-cols-1 md:grid-cols-2 gap-5 " data-phase-index="{{ $index }}">
                    @if (!empty($item['id']))
                        <input type="hidden" name="items[{{ $index }}][id]" value="{{ $item['id'] }}">
                    @endif
                    <input type="hidden" name="items[{{ $index }}][code]" value="{{ $item['code'] ?? '' }}" data-phase-code="{{ $index }}">
                    <label class="form-field acss-field-tight">
                        <span>Item Penilaian {{ $index + 1 }}</span>
                        <input type="text" name="items[{{ $index }}][name]" value="{{ $item['name'] ?? '' }}" required data-phase-name data-index="{{ $index }}" placeholder="Item Penilaian {{ $index + 1 }}">
                    </label>
                    <label class="form-field acss-field-tight">
                        <span>Bobot (%)</span>
                        <input type="number" name="items[{{ $index }}][weight]" value="{{ $item['weight'] ?? '' }}" min="1" max="100" required data-phase-weight placeholder="0-100">
                    </label>
                </div>
            @endforeach
        @endif
    </div>

    <small id="duplicate-item-error" class="field-error  block hidden">Nama item penilaian tidak boleh sama dalam satu format nilai.</small>

    @error('items')
        <small class="field-error  block">{{ $message }}</small>
    @enderror
</div>

<div class="flex justify-end  {{ $shouldShowRows ? '' : 'hidden' }}" id="total-weight-wrap">
    <div class="pill-row">
        <span class="pill text-sm font-semibold" id="total-weight-pill">Total Bobot: 0%</span>
    </div>
</div>
<script>
(() => {
    const root = document.getElementById('phase-builder-root');
    if (!root) return;

    const container = document.getElementById('phase-rows-container');
    const totalPill = document.getElementById('total-weight-pill');
    const totalWrap = document.getElementById('total-weight-wrap');
    const countSelect = document.getElementById('item-count-select');
    const form = root.closest('form');
    const emptyState = document.getElementById('phase-empty-state');
    const duplicateError = document.getElementById('duplicate-item-error');

    const slugify = (value) => value.toLowerCase().trim().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '');
    const normalizeName = (value) => value.toLowerCase().trim().replace(/\s+/g, ' ');
    const buildDefaultWeights = (count) => {
        if (!count || count < 1) return [];
        const base = Math.floor(100 / count);
        let remainder = 100 - (base * count);
        return Array.from({ length: count }, () => {
            const next = base + (remainder > 0 ? 1 : 0);
            if (remainder > 0) remainder -= 1;
            return next;
        });
    };

    const rowTemplate = (index, item = {}) => `
        <div class="acss-phase-block grid grid-cols-1 md:grid-cols-2 gap-5 " data-phase-index="${index}">
            ${item.id ? `<input type="hidden" name="items[${index}][id]" value="${item.id}">` : ''}
            <input type="hidden" name="items[${index}][code]" value="${item.code ?? ''}" data-phase-code="${index}">
            <label class="form-field acss-field-tight">
                <span>Item Penilaian ${index + 1}</span>
                <input type="text" name="items[${index}][name]" value="${item.name ?? ''}" required data-phase-name data-index="${index}" placeholder="Item Penilaian ${index + 1}">
            </label>
            <label class="form-field acss-field-tight">
                <span>Bobot (%)</span>
                <input type="number" name="items[${index}][weight]" value="${item.weight ?? ''}" min="1" max="100" required data-phase-weight placeholder="0-100">
            </label>
        </div>
    `;

    const rebuildRows = (count) => {
        if (!count || count < 1) {
            container.innerHTML = '';
            container.classList.add('hidden');
            emptyState?.classList.remove('hidden');
            totalWrap?.classList.add('hidden');
            duplicateError?.classList.add('hidden');
            updateTotal();
            return;
        }
        const defaults = buildDefaultWeights(count);
        container.classList.remove('hidden');
        emptyState?.classList.add('hidden');
        totalWrap?.classList.remove('hidden');
        const nextRows = Array.from({ length: count }, (_, index) => ({
            name: `Item Penilaian ${index + 1}`,
            code: `item_penilaian_${index + 1}`,
            weight: defaults[index] ?? '',
        }));
        container.innerHTML = nextRows.map((item, index) => rowTemplate(index, item)).join('');
        validateDuplicateNames();
        updateTotal();
    };

    const updateTotal = () => {
        const weights = Array.from(container.querySelectorAll('[data-phase-weight]')).map((input) => parseInt(input.value, 10) || 0);
        const total = weights.reduce((a, b) => a + b, 0);
        totalPill.textContent = `Total Bobot: ${total}%`;
        totalPill.style.color = total === 100 ? '#16a34a' : '#dc2626';
        return total;
    };

    const validateDuplicateNames = () => {
        const inputs = Array.from(container.querySelectorAll('[data-phase-name]'));
        const seen = new Map();
        let hasDuplicate = false;

        inputs.forEach((input) => {
            input.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        });

        inputs.forEach((input) => {
            const normalized = normalizeName(input.value || '');
            if (!normalized) return;

            if (!seen.has(normalized)) {
                seen.set(normalized, []);
            }

            seen.get(normalized).push(input);
        });

        seen.forEach((group) => {
            if (group.length < 2) return;
            hasDuplicate = true;
            group.forEach((input) => {
                input.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
            });
        });

        duplicateError?.classList.toggle('hidden', !hasDuplicate);
        return hasDuplicate;
    };

    countSelect?.addEventListener('change', (e) => {
        const value = parseInt(e.target.value, 10);
        rebuildRows(Number.isNaN(value) ? 0 : value);
    });

    container.addEventListener('input', (e) => {
        if (e.target.matches('[data-phase-weight]')) {
            updateTotal();
        }
        if (e.target.matches('[data-phase-name]')) {
            const index = e.target.dataset.index;
            const codeInput = container.querySelector(`[data-phase-code="${index}"]`);
            if (codeInput) {
                codeInput.value = slugify(e.target.value);
            }
            validateDuplicateNames();
        }
    });

    form?.addEventListener('submit', (e) => {
        if (!container.querySelector('[data-phase-weight]')) {
            e.preventDefault();
            alert('Pilih jumlah item penilaian terlebih dahulu.');
            return;
        }
        if (validateDuplicateNames()) {
            e.preventDefault();
            alert('Nama item penilaian tidak boleh sama dalam satu format nilai.');
            return;
        }
        if (updateTotal() !== 100) {
            e.preventDefault();
            alert('Total bobot item penilaian harus berjumlah 100%.');
        }
    });

    validateDuplicateNames();
    updateTotal();
})();
</script>
