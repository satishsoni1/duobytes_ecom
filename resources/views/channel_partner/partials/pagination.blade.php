@if ($paginator->hasPages())
    <nav class="cp-pagination" aria-label="{{ translate('Pagination') }}">
        @if ($paginator->onFirstPage())
            <span class="disabled" aria-hidden="true"><x-cp-icon name="arrow-left" class="cp-icon-sm" /></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ translate('Previous') }}"><x-cp-icon name="arrow-left" class="cp-icon-sm" /></a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="disabled">{{ $element }}</span>
            @endif
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ translate('Next') }}"><x-cp-icon name="arrow-right" class="cp-icon-sm" /></a>
        @else
            <span class="disabled" aria-hidden="true"><x-cp-icon name="arrow-right" class="cp-icon-sm" /></span>
        @endif
    </nav>
@endif
