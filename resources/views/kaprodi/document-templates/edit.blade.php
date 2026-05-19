@extends('layouts.app')

@section('content')
    @if (session('success'))
        <div class="notice notice--success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="notice notice--error">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('kaprodi.document-templates.update', $template) }}">
        @csrf
        @method('PUT')

        <div class="acss-form-stack-tight">
            <section class="acss-crud-card">
                <div class="acss-crud-head">
                    <div>
                        <h1 class="acss-page-title">Edit Dokumen Final</h1>
                        <p class="acss-muted">Perbarui periode terhubung dan struktur dokumen final.</p>
                    </div>
                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.document-templates.partials.form-metadata', ['template' => $template, 'periodes' => $periodes, 'activePeriodeId' => $activePeriodeId])
                </div>
            </section>

            <section class="acss-crud-card">
                <div class="acss-crud-head">
                    <div>
                        <h3 class="acss-card-title">Item Dokumen</h3>
                        <p class="acss-muted">Kelola daftar dokumen final pada template ini.</p>
                    </div>
                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.document-templates.partials.form-items', ['template' => $template])
                </div>
            </section>

            <div class="acss-page-card">
                <div class="acss-page-card__body">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <label class="form-field acss-field-tight md:w-1/3">
                            <span>Status Template</span>
                            <select name="status" required>
                                <option value="draft" {{ old('status', $template->status) === 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="published" {{ old('status', $template->status) === 'published' ? 'selected' : '' }}>Published</option>
                            </select>
                        </label>
                        <div class="acss-form-actions acss-form-actions--end">
                            <button class="button button--inline" type="submit">Simpan Perubahan</button>
                            <a class="button button--muted button--inline" href="{{ route('kaprodi.document-templates.show', $template) }}">Batal</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
