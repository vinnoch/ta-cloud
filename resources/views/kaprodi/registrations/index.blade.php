@extends('layouts.app')

@section('content')
    @php $registrations = $registrations ?? collect(); @endphp
    @if (session('success'))
        <div class="notice notice--success" role="status" aria-live="polite">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="notice notice--danger" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="card registration-review-card">
        <div class="section-heading acss-crud-head--inline">
            <div class="acss-crud-head__title-group">
                <p class="registration-eyebrow">Master Mahasiswa</p>
                <h1 class="acss-page-title">Pendaftaran mahasiswa</h1>
                <p class="acss-muted">Tinjau identitas dan kecocokan data sebelum mengaktifkan akun.</p>
            </div>
            <a class="button button--muted button--inline" href="{{ route('home') }}">Kembali</a>
        </div>

        <div class="registration-review-filters" role="group" aria-label="Filter status pendaftaran">
            @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                <a class="registration-filter {{ ($status ?? 'pending') === $value ? 'is-active' : '' }}" href="{{ request()->fullUrlWithQuery(['status' => $value]) }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="registration-review-list">
            @forelse ($registrations as $registration)
                @php
                    $approveRoute = route('registrations.approve', $registration);
                    $rejectRoute = route('registrations.reject', $registration);
                @endphp
                <article class="registration-review-item">
                    <div class="registration-review-item__identity">
                        <span class="registration-identity__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($registration->name, 0, 1)) }}</span>
                        <div><strong>{{ $registration->name }}</strong><span>{{ $registration->nim }} · {{ $registration->email }}</span></div>
                    </div>
                    <dl class="registration-evidence">
                        <div><dt>Metode</dt><dd>{{ ucfirst($registration->registration_method ?? 'email') }}</dd></div>
                        <div><dt>Program studi</dt><dd>{{ $registration->studyProgram?->name ?? '-' }}</dd></div>
                        <div><dt>Data master</dt><dd><span class="status-pill {{ ($registration->master_data_matches ?? false) ? 'status-pill--active' : 'status-pill--locked' }}">{{ ($registration->master_data_matches ?? false) ? 'Cocok' : 'Perlu diperiksa' }}</span></dd></div>
                        <div><dt>Dikirim</dt><dd>{{ optional($registration->created_at)->diffForHumans() ?? '-' }}</dd></div>
                    </dl>
                    @if ($registration->account_status === 'pending')
                        <div class="registration-review-actions">
                            <form method="POST" action="{{ $approveRoute }}">@csrf @method('PATCH')<button class="button button--inline" type="submit" @disabled($approveRoute === '#')>Setujui</button></form>
                            <details class="registration-reject">
                                <summary class="button button--muted button--inline">Tolak</summary>
                                <form method="POST" action="{{ $rejectRoute }}">@csrf @method('PATCH')<label for="rejection_reason_{{ $registration->id }}">Alasan penolakan</label><textarea id="rejection_reason_{{ $registration->id }}" name="rejection_reason" required maxlength="500"></textarea><button class="button button--danger button--inline" type="submit" @disabled($rejectRoute === '#')>Konfirmasi penolakan</button></form>
                            </details>
                        </div>
                    @endif
                </article>
            @empty
                <div class="empty-state registration-empty"><strong>Tidak ada pendaftaran pada status ini.</strong><span>Pendaftaran baru akan muncul setelah mahasiswa mengirim dan memverifikasi email.</span></div>
            @endforelse
        </div>
    </section>
@endsection
