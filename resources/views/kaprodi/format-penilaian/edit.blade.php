@extends('layouts.app')

@section('content')
    @if (session('duplicate_source_name'))
        <div class="notice notice--info">Anda sedang menduplikat {{ session('duplicate_source_name') }} untuk membuat format nilai baru.</div>
    @endif

    <form method="POST" action="{{ route('kaprodi.formats.update', $format) }}">
        @csrf
        @method('PUT')
        
        <div class="acss-form-stack-tight">
            <!-- Section 1: Metadata -->
            <section class="acss-crud-card">
                <div class="acss-crud-head">
                    <div>
                        <h1 class="acss-page-title">Edit Format Nilai</h1>
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

            @php $currentStatus = old('status', $format->status ?? 'draft'); @endphp
            <input type="hidden" name="status" value="{{ $currentStatus }}">
            @include('kaprodi.partials.publish-draft-footer', [
                'currentStatus' => $currentStatus,
                'cancelUrl' => route('kaprodi.formats.index'),
            ])
        </div>
    </form>

@endsection
