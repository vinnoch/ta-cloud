<div class="table-shell">
    @if ($skripsi->assignments->count() > 0)
        <div class="table-shell__head table-shell__grid acss-table-cols-reviewer">
            <span>Role</span><span>Dosen</span>
        </div>
    @endif
    @forelse ($skripsi->assignments as $assignment)
        <div class="table-shell__row table-shell__grid acss-table-cols-reviewer acss-hover-row-group acss-reviewer-role-normal-weight">
            <div class="table-shell__cell">
                <div><span>{{ ucfirst(str_replace('_', ' ', $assignment->role_type)) }}</span></div>
                <div class="acss-row-actions">
                    <button
                        class="text-link text-link--danger reviewer-remove-button acss-action-link"
                        type="button"
                        data-url="{{ route('kaprodi.skripsi.reviewers.destroy', [$skripsi, $assignment]) }}"
                    >
                        @include('partials.icons.x')<span>Remove</span>
                    </button>
                </div>
            </div>
            <div class="table-shell__cell">
                <div><strong>{{ $assignment->lecturer->name }}</strong></div>
                <div class="acss-muted text-xs">assigned {{ $assignment->created_at?->format('d/m/Y') }}</div>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada reviewer.</div>
    @endforelse
</div>
