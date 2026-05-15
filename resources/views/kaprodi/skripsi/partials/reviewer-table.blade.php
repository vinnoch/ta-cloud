<div class="table-shell">
    <div class="table-shell__head table-shell__grid acss-table-cols-reviewer">
        <span>Role</span><span>Dosen</span><span>Assigned</span>
    </div>
    @forelse ($skripsi->assignments as $assignment)
        <div class="table-shell__row table-shell__grid acss-table-cols-reviewer acss-hover-row-group">
            <div class="table-shell__cell">
                <div><strong>{{ ucfirst(str_replace('_', ' ', $assignment->role_type)) }}</strong></div>
                <div class="acss-row-actions">
                    <button
                        class="text-link text-link--danger reviewer-remove-button acss-action-link"
                        type="button"
                        data-url="{{ route('kaprodi.skripsi.reviewers.destroy', [$skripsi, $assignment]) }}"
                    >
                        @include('partials.icons.trash')<span>Unassign</span>
                    </button>
                </div>
            </div>
            <div class="table-shell__cell">{{ $assignment->lecturer->name }}</div>
            <div class="table-shell__cell">
                <div>{{ $assignment->created_at?->format('d/m/Y') }}</div>
            </div>
        </div>
    @empty
        <div class="empty-state">Belum ada reviewer.</div>
    @endforelse
</div>
