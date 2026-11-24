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
                    </div>

                </div>
                <div class="acss-crud-body">
                    @include('kaprodi.format-penilaian.partials.form-items', ['format' => $format])
                </div>
            </section>

            @php $currentStatus = old('status', 'draft'); @endphp
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            @include('kaprodi.partials.publish-draft-footer', [
                'currentStatus' => $currentStatus,
                'cancelUrl' => route('kaprodi.formats.index'),
            ])
        </div>
    </form>

@endsection
