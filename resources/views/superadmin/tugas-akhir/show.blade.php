@extends('layouts.app')

@section('content')
    @php
        $label = $tugasAkhirType === 'non_skripsi' ? 'Non-Skripsi' : 'Skripsi';
        $routePrefix = $tugasAkhirType === 'non_skripsi' ? 'superadmin.non-skripsi' : 'superadmin.skripsi';
    @endphp

    <section class="acss-crud-card">
        <div class="acss-crud-head acss-crud-head--inline">
            <div>
                <p class="acss-muted">{{ $label }}</p>
                <h1 class="acss-page-title">{{ $skripsi->title }}</h1>
            </div>
            <a class="button button--muted button--inline" href="{{ route($routePrefix.'.index') }}">Kembali</a>
        </div>

        <div class="acss-crud-body">
            <div class="grid-two">
                <div><span class="label-muted">Mahasiswa</span><p>{{ $skripsi->student?->name ?? '-' }} · {{ $skripsi->student?->nim ?? '-' }}</p></div>
                <div><span class="label-muted">Periode</span><p>{{ $skripsi->periode?->name ?? '-' }}</p></div>
                <div><span class="label-muted">Fase</span><p><span class="pill">{{ str($skripsi->current_phase)->replace(['_', '-'], ' ')->title() }}</span></p></div>
                <div><span class="label-muted">Pembimbing/Reviewer</span><p>{{ $skripsi->assignments->count() }} penugasan</p></div>
                <div><span class="label-muted">Dokumen</span><p>{{ $skripsi->documentVersions->count() }} versi</p></div>
            </div>
        </div>
    </section>
@endsection
