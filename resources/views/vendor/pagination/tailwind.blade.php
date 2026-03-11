@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation" class="crm-pagination-nav">
        <div class="crm-pagination-mobile sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="crm-pagination-btn crm-pagination-btn-disabled">
                    Previous
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="crm-pagination-btn">
                    Previous
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="crm-pagination-btn">
                    Next
                </a>
            @else
                <span class="crm-pagination-btn crm-pagination-btn-disabled">
                    Next
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:items-center sm:justify-between sm:gap-3">
            <p class="crm-pagination-meta">
                Showing
                @if ($paginator->firstItem())
                    <span class="font-semibold">{{ $paginator->firstItem() }}</span>
                    to
                    <span class="font-semibold">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                of
                <span class="font-semibold">{{ $paginator->total() }}</span>
                results
            </p>

            <span class="crm-pagination-list" aria-label="Pagination">
                @if ($paginator->onFirstPage())
                    <span class="crm-pagination-item crm-pagination-item-disabled" aria-hidden="true">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="crm-pagination-item"
                        aria-label="Previous">
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="crm-pagination-item crm-pagination-item-disabled">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="crm-pagination-item crm-pagination-item-active"
                                    aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="crm-pagination-item"
                                    aria-label="Go to page {{ $page }}">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="crm-pagination-item"
                        aria-label="Next">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </a>
                @else
                    <span class="crm-pagination-item crm-pagination-item-disabled" aria-hidden="true">
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
