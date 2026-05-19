@if ($assignedSkripsis instanceof \Illuminate\Pagination\LengthAwarePaginator && $assignedSkripsis->hasPages())
    <div class="pagination-shell acss-pagination-spacer">{{ $assignedSkripsis->links() }}</div>
@endif
