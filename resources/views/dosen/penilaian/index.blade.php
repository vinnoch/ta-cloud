@extends('layouts.app')
@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="notice notice--danger">{{ $errors->first() }}</div>
    @endif

    <section class="acss-crud-card" id="grade-list-root" data-endpoint="{{ route('dosen.penilaian.index') }}">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Antrian Penilaian</h1>
                <p id="grade-count-text" class="acss-muted ">{{ $gradingQueue->total() }} antrian ditemukan.</p>
            </div>
        </div>
        <div class="acss-crud-body">
            <form class="filter-bar" id="grade-filter-form" method="GET" action="{{ route('dosen.penilaian.index') }}">
                <input type="hidden" name="sort" value="{{ $sort ?? 'tanggal' }}">
                <input type="hidden" name="direction" value="{{ $direction ?? 'desc' }}">
                <label class="form-field acss-search-field">
                    <span>Cari Mahasiswa/Judul</span>
                    <input id="grade-search-input" type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari NIM, nama, atau judul">
                </label>
                <label class="form-field acss-field-tight">
                    <span>Nilai Sidang</span>
                    <select name="nilai_sidang" id="grade-nilai-sidang-select">
                        <option value="">Semua</option>
                        <option value="sidang_proposal" {{ ($nilaiSidang ?? '') === 'sidang_proposal' ? 'selected' : '' }}>Sidang Proposal</option>
                        <option value="sidang_skripsi" {{ ($nilaiSidang ?? '') === 'sidang_skripsi' ? 'selected' : '' }}>Sidang Skripsi</option>
                    </select>
                </label>
            </form>
            <div id="grade-table-wrapper">@include('dosen.penilaian.partials.table', ['gradingQueue' => $gradingQueue, 'sort' => $sort, 'direction' => $direction])</div>
            <div id="grade-pagination-wrapper">@include('dosen.penilaian.partials.pagination', ['gradingQueue' => $gradingQueue])</div>
        </div>
    </section>
    @include('partials.ajax-list-script', ['rootId'=>'grade-list-root','formId'=>'grade-filter-form','searchInputId'=>'grade-search-input','statusSelectId'=>'grade-nilai-sidang-select','tableWrapperId'=>'grade-table-wrapper','paginationWrapperId'=>'grade-pagination-wrapper','countTextId'=>'grade-count-text'])

    <script>
    (() => {
        const toggleModal = (modal, show) => {
            if (!modal) return;
            modal.hidden = !show;
            document.body.classList.toggle('overflow-hidden', show);
            if (show) syncSummaryForForm(modal.querySelector('[data-grading-form]'));
        };

        const formatNumber = (value) => {
            if (Number.isNaN(value)) return '-';
            return value.toFixed(2).replace(/\.00$/, '').replace(/(\.\d*[1-9])0$/, '$1');
        };

        const clampScore = (value) => Math.max(0, Math.min(100, value));

        const syncSummaryForForm = (form) => {
            if (!form) return;
            const inputs = Array.from(form.querySelectorAll('[data-score-input]'));
            const weightedNode = form.querySelector('[data-weighted-score]');
            const filled = inputs.map((input) => Number.parseFloat(input.value)).filter((value) => !Number.isNaN(value));
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

        const shiftScore = (input, delta) => {
            const raw = Number.parseFloat(input.value);
            const current = Number.isNaN(raw) ? 0 : raw;
            const decimals = (String(input.value || '').split('.')[1] || '').length;
            const next = clampScore(current + delta);
            input.value = decimals > 0 ? next.toFixed(decimals) : String(Math.round(next));
            syncSummaryForForm(input.closest('[data-grading-form]'));
        };

        document.addEventListener('click', (event) => {
            const openButton = event.target.closest('[data-grade-modal-open]');
            if (openButton) {
                const modal = document.querySelector(`[data-grade-modal="${openButton.dataset.gradeModalOpen}"]`);
                toggleModal(modal, true);
                return;
            }
            if (event.target.closest('[data-grade-modal-close]')) {
                toggleModal(event.target.closest('.acss-modal'), false);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                document.querySelectorAll('[data-grade-modal]').forEach((modal) => toggleModal(modal, false));
            }
            const input = event.target.closest('[data-score-input]');
            if (!input) return;
            if (event.key === 'ArrowUp') {
                event.preventDefault();
                shiftScore(input, 10);
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                shiftScore(input, -10);
            }
        });

        document.addEventListener('input', (event) => {
            const input = event.target.closest('[data-score-input]');
            if (!input) return;
            syncSummaryForForm(input.closest('[data-grading-form]'));
        });

        document.addEventListener('wheel', (event) => {
            const input = event.target.closest('[data-score-input]');
            if (!input || document.activeElement !== input) return;
            event.preventDefault();
            shiftScore(input, event.deltaY < 0 ? 10 : -10);
        }, { passive: false });
    })();
    </script>
@endsection
