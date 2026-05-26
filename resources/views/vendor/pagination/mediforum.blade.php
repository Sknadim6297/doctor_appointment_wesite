@if ($paginator->total() > 0 || $paginator->hasPages())
    <nav class="mf-pagination" role="navigation" aria-label="Pagination">
        <p class="mf-pagination__summary">
            @if ($paginator->total() > 0)
                Showing <strong>{{ $paginator->firstItem() }}</strong> to <strong>{{ $paginator->lastItem() }}</strong> of <strong>{{ $paginator->total() }}</strong> results
            @else
                No results to display
            @endif
        </p>

        @if ($paginator->hasPages())
        <div class="mf-pagination__mobile">
            @if ($paginator->onFirstPage())
                <span class="mf-pagination__btn mf-pagination__btn--disabled" aria-disabled="true">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i> Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="mf-pagination__btn">
                    <i class="ri-arrow-left-line" aria-hidden="true"></i> Previous
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="mf-pagination__btn">
                    Next <i class="ri-arrow-right-line" aria-hidden="true"></i>
                </a>
            @else
                <span class="mf-pagination__btn mf-pagination__btn--disabled" aria-disabled="true">
                    Next <i class="ri-arrow-right-line" aria-hidden="true"></i>
                </span>
            @endif
        </div>

        <ul class="mf-pagination__list">
            {{-- Previous --}}
            <li>
                @if ($paginator->onFirstPage())
                    <span class="mf-pagination__control mf-pagination__control--disabled" aria-hidden="true">
                        <i class="ri-arrow-left-s-line"></i>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="mf-pagination__control" aria-label="Previous page">
                        <i class="ri-arrow-left-s-line"></i>
                    </a>
                @endif
            </li>

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li>
                        <span class="mf-pagination__ellipsis" aria-hidden="true">{{ $element }}</span>
                    </li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <li>
                            @if ($page == $paginator->currentPage())
                                <span class="mf-pagination__page mf-pagination__page--active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="mf-pagination__page" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                            @endif
                        </li>
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li>
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="mf-pagination__control" aria-label="Next page">
                        <i class="ri-arrow-right-s-line"></i>
                    </a>
                @else
                    <span class="mf-pagination__control mf-pagination__control--disabled" aria-hidden="true">
                        <i class="ri-arrow-right-s-line"></i>
                    </span>
                @endif
            </li>
        </ul>
        @endif
    </nav>
@endif
