@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice notice--danger">{{ $errors->first() }}</div>
    @endif

    <section class="acss-page-card">
        <div class="acss-page-card__body">
            <h1 class="acss-page-title">Form Penilaian dan Revisi Sidang</h1>
            <p class="acss-muted ">Input nilai sidang skripsi untuk mahasiswa yang Anda bimbing atau uji.</p>
        </div>
    </section>

    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student?->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>{{ $skripsi->student?->name ?? 'Mahasiswa' }}</h2>
                        <p>{{ $skripsi->student?->nim ?? '-' }} • {{ $skripsi->periode?->name ?? '-' }}</p>
                        <div class="acss-grade-title">{{ $skripsi->title ?: 'Tanpa Judul' }}</div>
                    </div>
                    <div class="acss-profile-badges acss-profile-badges--centered">
                        <span class="pill pill--blue">{{ str($assignment->role_type)->replace('_', ' ')->title() }}</span>
                        @if ($grade?->status)<span class="pill">{{ $grade->locked_at ? 'LOCKED' : strtoupper($grade->status) }}</span>@endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="acss-section-card acss-section-card--grading">
        <div class="acss-section-card__head">
            <div>
                <h3 class="acss-card-title">Item Penilaian</h3>
                <p class="acss-muted ">Isi seluruh skor sesuai bobot format nilai sidang.</p>
            </div>
        </div>
        <div class="acss-section-card__body">
            <form method="POST" action="{{ route('dosen.penilaian.store', $skripsi) }}" class="acss-form-stack-tight" data-grading-form>
                @csrf

                <div class="rubric-list">
                    @foreach ($format->items as $item)
                        <article class="rubric-item">
                            <div class="rubric-item__content">
                                <h4>{{ $item->nama }}</h4>
                                <p class="rubric-item__meta">{{ rtrim(rtrim(number_format((float) $item->bobot, 2, '.', ''), '0'), '.') }}% Bobot</p>
                            </div>
                            <div class="rubric-item__score">
                                <label class="score-box">
                                    <input
                                        type="number"
                                        name="scores[{{ $item->id }}]"
                                        min="0"
                                        max="100"
                                        step="any"
                                        value="{{ old('scores.' . $item->id, $itemScores[$item->id] ?? '') }}"
                                        placeholder="0"
                                        required
                                        @disabled($isLocked)
                                        data-score-input
                                        data-weight="{{ (float) $item->bobot }}"
                                    />
                                </label>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="acss-grading-summary acss-grading-summary--single" data-grading-summary>
                    <div class="acss-grading-summary__item acss-grading-summary__item--single">
                        <span class="acss-grading-summary__label">Total Nilai</span>
                        <strong class="acss-grading-summary__value" data-weighted-score>-</strong>
                    </div>
                </div>

                <label class="form-field">
                    <span>Catatan Revisi</span>
                    <textarea name="notes" rows="4" placeholder="Masukkan catatan revisi untuk mahasiswa..." @disabled($isLocked)>{{ old('notes', $grade?->notes) }}</textarea>
                </label>

                @if (! $isLocked)
                    <div class="acss-page-card">
                        <div class="acss-page-card__body">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                                <label class="form-field acss-field-tight md:w-1/3">
                                    <span>Status Nilai</span>
                                    <select name="save_mode" required>
                                        <option value="draft" {{ old('save_mode', $grade?->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="publish_lock" {{ old('save_mode') === 'publish_lock' ? 'selected' : '' }}>Publish dan Kunci</option>
                                    </select>
                                </label>
                                <div class="acss-form-actions acss-form-actions--end">
                                    <button class="button button--success button--inline" type="submit">
                                        {{ $grade ? 'Edit Nilai' : 'Simpan Nilai' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </form>

            @if ($isLocked)
                <div class="acss-page-card">
                    <div class="acss-page-card__body">
                        <div class="acss-form-actions acss-form-actions--end">
                            <form method="POST" action="{{ route('dosen.penilaian.request-unlock', $skripsi) }}">
                                @csrf
                                <button class="button button--danger button--inline" type="submit">
                                    {{ $unlockRequested ? 'Menunggu Buka Kunci' : 'Request Buka Kunci Nilai' }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('[data-grading-form]');
            if (!form) return;

            const inputs = Array.from(form.querySelectorAll('[data-score-input]'));
            const weightedNode = form.querySelector('[data-weighted-score]');

            const formatNumber = (value) => {
                if (Number.isNaN(value)) return '-';
                return value.toFixed(2).replace(/\.00$/, '').replace(/(\.\d*[1-9])0$/, '$1');
            };

            const clampScore = (value) => Math.max(0, Math.min(100, value));

            const shiftScore = (input, delta) => {
                const raw = Number.parseFloat(input.value);
                const current = Number.isNaN(raw) ? 0 : raw;
                const decimals = (String(input.value || '').split('.')[1] || '').length;
                const next = clampScore(current + delta);
                input.value = decimals > 0 ? next.toFixed(decimals) : String(Math.round(next));
                syncSummary();
            };

            const syncSummary = () => {
                const filled = inputs
                    .map((input) => Number.parseFloat(input.value))
                    .filter((value) => !Number.isNaN(value));

                if (!filled.length) {
                    if (weightedNode) weightedNode.textContent = '-';
                    return;
                }

                const weighted = inputs.reduce((sum, input) => {
                    const value = Number.parseFloat(input.value);
                    const weight = Number.parseFloat(input.dataset.weight || '0');
                    if (Number.isNaN(value) || Number.isNaN(weight)) return sum;
                    return sum + (value * (weight / 100));
                }, 0);

                if (weightedNode) weightedNode.textContent = formatNumber(weighted);
            };

            inputs.forEach((input) => {
                input.addEventListener('input', syncSummary);
                input.addEventListener('change', syncSummary);
                input.addEventListener('keydown', (event) => {
                    if (event.key === 'ArrowUp') {
                        event.preventDefault();
                        shiftScore(input, 10);
                    }
                    if (event.key === 'ArrowDown') {
                        event.preventDefault();
                        shiftScore(input, -10);
                    }
                });
                input.addEventListener('wheel', (event) => {
                    if (document.activeElement !== input) return;
                    event.preventDefault();
                    shiftScore(input, event.deltaY < 0 ? 10 : -10);
                }, { passive: false });
            });

            syncSummary();
        });
    </script>
@endpush
