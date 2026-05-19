<div class="acss-modal" data-grade-modal="{{ $item['modal_id'] }}" hidden>
    <div class="acss-modal__backdrop" data-grade-modal-close></div>
    <div class="acss-modal__dialog acss-modal__dialog--large acss-modal__dialog--master">
        <div class="acss-modal__head">
            <div>
                <h3 class="acss-card-title">{{ $item['has_grade'] ? 'Edit Nilai' : 'Isi Nilai' }}</h3>
                <p class="acss-muted">{{ $item['student'] }} • {{ $item['nim'] }}</p>
            </div>
            <button type="button" class="acss-modal__close" data-grade-modal-close aria-label="Tutup">×</button>
        </div>
        <form method="POST" action="{{ $item['store_url'] }}" class="acss-form-stack-tight" data-grading-form>
            @csrf
            <input type="hidden" name="redirect_to" value="{{ route('dosen.penilaian.index') }}">

            <div class="acss-master-form-shell">
                <div class="profile-card__subline" style="padding: 0 0 .25rem;">
                    <span>{{ $item['title'] }}</span>
                    <span>•</span>
                    <span>{{ $item['fase'] }}</span>
                    <span>•</span>
                    <span>{{ $item['role'] }}</span>
                </div>

                <div class="rubric-list">
                    @foreach ($item['format']->items as $formatItem)
                        <article class="rubric-item">
                            <div class="rubric-item__content">
                                <h4>{{ $formatItem->nama }}</h4>
                                <p class="rubric-item__meta">{{ rtrim(rtrim(number_format((float) $formatItem->bobot, 2, '.', ''), '0'), '.') }}% Bobot</p>
                            </div>
                            <div class="rubric-item__score">
                                <label class="score-box">
                                    <input
                                        type="number"
                                        name="scores[{{ $formatItem->id }}]"
                                        min="0"
                                        max="100"
                                        step="any"
                                        value="{{ $item['itemScores'][$formatItem->id] ?? '' }}"
                                        placeholder="0"
                                        required
                                        data-score-input
                                        data-weight="{{ (float) $formatItem->bobot }}"
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
                    <textarea name="notes" rows="4" placeholder="Masukkan catatan revisi untuk mahasiswa...">{{ $item['grade']?->notes }}</textarea>
                </label>
            </div>

            <div class="acss-page-card">
                <div class="acss-page-card__body">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <label class="form-field acss-field-tight md:w-1/3">
                            <span>Status Nilai</span>
                            <select name="save_mode" required>
                                <option value="draft" {{ ($item['grade']?->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="publish_lock">Publish dan Kunci</option>
                            </select>
                        </label>
                        <div class="acss-form-actions acss-form-actions--end">
                            <button type="button" class="button button--muted button--inline" data-grade-modal-close>Batal</button>
                            <button class="button button--success button--inline" type="submit">Simpan Nilai</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
