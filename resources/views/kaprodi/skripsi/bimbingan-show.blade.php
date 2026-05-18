@extends('layouts.app')

@section('content')
    <section class="card card--profile">
        <div class="profile-card">
            <div class="profile-card__avatar">{{ mb_substr($skripsi->student->name ?? 'M', 0, 1) }}</div>
            <div class="profile-card__main">
                <div class="profile-card__meta">
                    <div>
                        <h2>Detail Histori Bimbingan</h2>
                        <p>{{ $skripsi->student->name ?? '-' }} • {{ $skripsi->student->nim ?? '-' }}</p>
                        <div class="acss-quote-title">{{ $skripsi->title }}</div>
                    </div>
                    <span class="status-pill">{{ str($bimbingan->phase)->replace(['_', '-'], ' ')->upper() }}</span>
                </div>
                <div class="form-actions form-actions--inline ">
                    
                </div>
            </div>
        </div>
    </section>

    <section class="acss-section-card">
        <div class="acss-section-card__body acss-form-stack-tight">
            <div class="form-field"><span>Tanggal Bimbingan</span><strong>{{ $bimbingan->meeting_date?->format('d/m/Y') ?? '-' }}</strong></div>
            <div class="form-field"><span>Nama Dosen</span><strong>{{ $bimbingan->reviewer?->name ?? '-' }}</strong></div>
            <p>{{ $bimbingan->lecturer_notes ?? 'Tidak ada catatan.' }}</p>
        </div>
    </section>

    @if ($bimbingan->revision_file_url)
        <section class="acss-section-card">
            <div class="acss-section-card__head"><div><h3 class="acss-card-title">Preview File Revisi</h3></div></div>
            <div class="acss-section-card__body">
                <iframe src="{{ asset($bimbingan->revision_file_url) }}" width="100%" height="800px" style="border: none; border-radius: 8px;"></iframe>
            </div>
        </section>
    @endif
@endsection
