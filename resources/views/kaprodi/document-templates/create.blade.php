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
        .document-item-row .form-field {
            gap: .58rem;
        }

        .acss-meta-grid-tight .form-field > span,
        .document-item-row .form-field > span {
            margin-bottom: .05rem;
        }
    </style>
    <form method="POST" action="{{ route('kaprodi.document-templates.store') }}">
        @csrf

        <div class="acss-form-stack-tight">
            <section class="acss-crud-card">
                <div class="acss-crud-head">
                    <div>
                        <h1 class="acss-page-title">Tambah Dokumen Final</h1>
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
                        <p class="acss-muted">Tambahkan daftar dokumen final seperti skripsi, dataset, atau surat pendukung.</p>
                    </div>
                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.document-templates.partials.form-items', ['template' => $template])
                </div>
            </section>

            @php $currentStatus = old('status', 'draft'); @endphp
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            @include('kaprodi.partials.publish-draft-footer', [
                'currentStatus' => $currentStatus,
                'cancelUrl' => route('kaprodi.document-templates.index'),
            ])
        </div>
    </form>
@endsection
