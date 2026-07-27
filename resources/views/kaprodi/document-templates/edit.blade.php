@extends('layouts.app')

@section('content')
    @if (session('success') || request()->boolean('saved'))
        <div class="notice notice--success">{{ session('success', 'Template dokumen final berhasil diperbarui.') }}</div>
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
                    @php $currentStatus = old('status', $template->is_published ? 'published' : 'draft'); @endphp
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <span class="acss-muted">Status saat ini: {{ strtoupper($currentStatus) }}</span>
                        </div>
                        <div class="acss-form-actions acss-form-actions--end">
                            <a class="button button--muted button--inline" href="{{ route('kaprodi.document-templates.show', $template) }}">Batal</a>
                            @if ($currentStatus === 'draft')
                                <button class="button button--inline" type="submit" name="status" value="draft">Simpan Draft</button>
                                <button class="button button--success button--inline" type="submit" name="status" value="published">Publish</button>
                            @else
                                <button class="button button--success button--inline" type="submit" name="status" value="published">Simpan Perubahan</button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection
