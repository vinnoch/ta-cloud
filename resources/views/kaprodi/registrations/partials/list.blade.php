<div class="registration-review-list">
    @forelse ($registrations as $registration)
        @php
            $approveRoute = route('registrations.approve', $registration);
            $rejectRoute = route('registrations.reject', $registration);
            $identifier = $registration->role === 'mahasiswa' ? $registration->nim : $registration->nidn_nip;
        @endphp
        <article class="registration-review-item">
            <div class="registration-review-item__identity">
                <span class="registration-identity__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($registration->name, 0, 1)) }}</span>
                <div><strong>{{ $registration->name }}</strong><span>{{ $identifier ?: '-' }} · {{ $registration->email }}</span></div>
            </div>
            <dl class="registration-evidence">
                <div><dt>Peran</dt><dd><span class="status-pill">{{ str($registration->role)->title() }}</span></dd></div>
                <div><dt>Metode</dt><dd>{{ ucfirst($registration->registration_method ?? 'email') }}</dd></div>
                <div><dt>Program studi</dt><dd>{{ $registration->studyProgram?->name ?? '-' }}</dd></div>
                <div><dt>Data master</dt><dd><span class="status-pill {{ ($registration->master_data_matches ?? false) ? 'status-pill--active' : 'status-pill--locked' }}">{{ ($registration->master_data_matches ?? false) ? 'Cocok' : 'Perlu diperiksa' }}</span></dd></div>
                <div><dt>Dikirim</dt><dd>{{ optional($registration->created_at)->diffForHumans() ?? '-' }}</dd></div>
            </dl>
            @if ($registration->account_status === 'pending')
                <div class="registration-review-actions">
                    <form method="POST" action="{{ $approveRoute }}">@csrf @method('PATCH')<button class="button button--inline" type="submit">Setujui</button></form>
                    <details class="registration-reject">
                        <summary class="button button--muted button--inline">Tolak</summary>
                        <form method="POST" action="{{ $rejectRoute }}">@csrf @method('PATCH')<label for="rejection_reason_{{ $registration->id }}">Alasan penolakan</label><textarea id="rejection_reason_{{ $registration->id }}" name="rejection_reason" required maxlength="500"></textarea><button class="button button--danger button--inline" type="submit">Konfirmasi penolakan</button></form>
                    </details>
                </div>
            @endif
        </article>
    @empty
        <div class="empty-state registration-empty"><strong>Tidak ada pendaftaran pada filter ini.</strong><span>Pendaftaran baru muncul setelah pengguna mengirim data melalui Google.</span></div>
    @endforelse
</div>
