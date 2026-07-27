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
                        <p class="acss-muted ">Perbarui metadata format dan bobot item penilaian.</p>
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
                    @php $currentStatus = old('status', $format->status ?? 'draft'); @endphp
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                        <div>
                            <span class="acss-muted">Status saat ini: {{ strtoupper($currentStatus) }}</span>
                        </div>
                        <div class="acss-form-actions acss-form-actions--end">
                            <a class="button button--muted button--inline" href="{{ route('kaprodi.formats.index') }}">Batal</a>
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
