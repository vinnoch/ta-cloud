@extends('layouts.app')

@section('content')
    <section class="acss-crud-card">
        <div class="acss-crud-head">
            <div>
                <h1 class="acss-page-title">Export Skripsi</h1>
                <p class="acss-muted">Export data skripsi ke CSV berdasarkan filter yang dipilih.</p>
            </div>
        </div>

        <div class="acss-crud-body">
            <form class="acss-export-form" method="GET" action="{{ route('kaprodi.skripsi.export.csv') }}" target="_blank">
                <div class="acss-export-form__fields">
                    <label class="form-field acss-search-field">
                        <span>Pencarian</span>
                        <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Cari judul TA, nama, atau NIM">
                    </label>

                    <label class="form-field">
                        <span>Fase</span>
                        <select name="status">
                            <option value="">Semua</option>
                            @foreach (['Proposal','Sidang Proposal','Bimbingan Skripsi','Sidang Skripsi','Revisi Sidang Skripsi','Review Dokumen Final','Skripsi Selesai'] as $option)
                                <option value="{{ $option }}" {{ ($status ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="form-field">
                        <span>Periode Akademik</span>
                        <select name="periode_id">
                            <option value="">Semua</option>
                            @foreach (($periodes ?? collect()) as $periode)
                                <option value="{{ $periode->id }}" {{ (int) ($periodeId ?? 0) === (int) $periode->id ? 'selected' : '' }}>
                                    {{ $periode->name }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <div class="form-actions form-actions--inline acss-export-form__actions">
                    <button type="submit" class="button button--inline">
                        @include('partials.icons.download')
                        <span>Export CSV</span>
                    </button>
                </div>
            </form>
        </div>
    </section>
@endsection
