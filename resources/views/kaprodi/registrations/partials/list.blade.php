<div class="table-shell pilot-table">
    @if ($registrations->isNotEmpty())
        <div class="table-shell__head table-shell__grid" style="--table-cols:minmax(14rem,1.4fr) minmax(8rem,.65fr) minmax(11rem,.9fr) minmax(10rem,.8fr) minmax(12rem,1fr)">
            <span>Pengguna</span>
            <span>Peran</span>
            <span>Program Studi</span>
            <span>Status Data</span>
            <span>Tindakan</span>
        </div>
    @endif
    @forelse ($registrations as $registration)
        @php
            $approveRoute = route('registrations.approve', $registration);
            $rejectRoute = route('registrations.reject', $registration);
            $identifier = $registration->role === 'mahasiswa' ? $registration->nim : $registration->nidn_nip;
        @endphp
        <article class="table-shell__row table-shell__grid pilot-record-card" style="--table-cols:minmax(14rem,1.4fr) minmax(8rem,.65fr) minmax(11rem,.9fr) minmax(10rem,.8fr) minmax(12rem,1fr)">
            <div class="table-shell__cell pilot-record-card__identity registration-review-item__identity">
                <span class="registration-identity__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($registration->name, 0, 1)) }}</span>
                <div><strong>{{ $registration->name }}</strong><small>{{ $identifier ?: '-' }} · {{ $registration->email }}</small></div>
            </div>
            <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Peran"><span class="status-pill">{{ str($registration->role)->title() }}</span><small>{{ ucfirst($registration->registration_method ?? 'email') }}</small></div>
            <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Program Studi">{{ $registration->studyProgram?->name ?? '-' }}</div>
            <div class="table-shell__cell pilot-record-card__meta" data-mobile-label="Status Data"><span class="status-pill {{ ($registration->master_data_matches ?? false) ? 'status-pill--active' : 'status-pill--locked' }}">{{ ($registration->master_data_matches ?? false) ? 'Cocok' : 'Perlu diperiksa' }}</span><small>{{ optional($registration->created_at)->diffForHumans() ?? '-' }}</small></div>
            <div class="table-shell__cell registration-review-actions">
                @if ($registration->account_status === 'pending')
                    <form method="POST" action="{{ $approveRoute }}">@csrf @method('PATCH')<button class="button button--inline" type="submit">Setujui</button></form>
                    <details class="registration-reject">
                        <summary class="button button--muted button--inline">Tolak</summary>
                        <form method="POST" action="{{ $rejectRoute }}">@csrf @method('PATCH')<label for="rejection_reason_{{ $registration->id }}">Alasan penolakan</label><textarea id="rejection_reason_{{ $registration->id }}" name="rejection_reason" required maxlength="500"></textarea><button class="button button--danger button--inline" type="submit">Konfirmasi penolakan</button></form>
                    </details>
                @else
                    <span class="pill">{{ str($registration->account_status)->title() }}</span>
                @endif
            </div>
        </article>
    @empty
        <div class="empty-state">Tidak ada pendaftaran pada filter ini.</div>
    @endforelse
</div>
