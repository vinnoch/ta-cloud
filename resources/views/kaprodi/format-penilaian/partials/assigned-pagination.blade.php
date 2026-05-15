@if ($assignedSkripsis instanceof \Illuminate\Pagination\LengthAwarePaginator && $assignedSkripsis->hasPages())
    <div class="pagination-shell">
        {{ $assignedSkripsis->links() }}
    </div>
@endif
