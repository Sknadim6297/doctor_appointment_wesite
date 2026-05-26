@if (isset($paginator) && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div class="mf-pagination-wrap">
        {{ $paginator->withQueryString()->links() }}
    </div>
@endif
