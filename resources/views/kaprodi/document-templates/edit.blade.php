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
                    </div>
                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.document-templates.partials.form-items', ['template' => $template])
                </div>
            </section>

            @php $currentStatus = old('status', $template->is_published ? 'published' : 'draft'); @endphp
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            @include('kaprodi.partials.publish-draft-footer', [
                'currentStatus' => $currentStatus,
                'cancelUrl' => route('kaprodi.document-templates.show', $template),
            ])
        </div>
    </form>
@endsection
