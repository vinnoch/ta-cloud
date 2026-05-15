@if ($data_nilai->hasPages())
    <div class="acss-pagination">
        {{ $data_nilai->links() }}
    </div>
@endif
