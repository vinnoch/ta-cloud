@extends('layouts.app')

@section('content')
    <style>
        .acss-meta-grid-tight {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1.45rem;
        }
        @media (min-width: 768px) {
            .acss-meta-grid-tight {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .acss-meta-grid-tight .form-field,
        .acss-phase-block .form-field {
            gap: .58rem;
        }

        .acss-meta-grid-tight .form-field > span,
        .acss-phase-block .form-field > span {
            margin-bottom: .05rem;
        }

        .acss-phase-block {
            margin-bottom: 1.6rem !important;
            gap: 1.45rem !important;
        }
    </style>
    <form method="POST" action="{{ route('kaprodi.formats.store') }}">
        @csrf
        
        <div class="acss-form-stack-tight">
            <!-- Section 1: Metadata -->
            <section class="acss-crud-card">
                <div class="acss-crud-head">
                    <div>
                        <h1 class="acss-page-title">Tambah Format Nilai</h1>
                    </div>
                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.format-penilaian.partials.form-metadata', ['format' => $format, 'periodes' => $periodes, 'studyProgram' => $studyProgram ?? null])
                </div>
            </section>

            <!-- Section 2: Items -->
            <section class="acss-crud-card">
                <div class="acss-crud-head">
                    <div>
                        <h3 class="acss-card-title">Item Penilaian</h3>
                        <p class="acss-muted ">Susun item penilaian sidang dan persentase bobotnya.</p>
                    </div>

                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.format-penilaian.partials.form-items', ['format' => $format])
                </div>
            </section>

            <!-- Action Card with Status -->
            <div class="acss-page-card">
                <div class="acss-page-card__body">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <label class="form-field acss-field-tight md:w-1/3">
                            <span>Status Format</span>
                            <select name="status" required>
                                <option value="draft" {{ old('status', $format->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ old('status', $format->status ?? '') === 'published' ? 'selected' : '' }}>Published</option>
                            </select>
                        </label>
                        <div class="acss-form-actions acss-form-actions--end">
                            <a class="button button--muted button--inline" href="{{ route('kaprodi.formats.index') }}">Batal</a>
                            <button class="button button--success button--inline" type="submit">Simpan Format</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

@endsection
