@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman">
        {{-- Unified responsive pager: compact circular buttons with chevrons and page numbers
             on both mobile (centered) and desktop (aligned right), matching DESIGN.md tokens.
             Every target stays 44x44 from the smallest screen up: the pager is a tap target
             on phones, not a desktop-only control. --}}
        <div class="flex flex-1 gap-1 sm:gap-1.5 items-center justify-center sm:justify-end flex-wrap">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="Sebelumnya" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-on-surface-variant/40">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
                    class="inline-flex h-11 w-11 items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface active:bg-surface-container-highest">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                    </svg>
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="inline-flex h-11 items-center px-1.5 text-sm font-medium text-on-surface-variant">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="inline-flex h-11 w-11 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Halaman {{ $page }}"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-full text-sm font-medium text-on-surface transition hover:bg-surface-container-high hover:text-on-surface active:bg-surface-container-highest">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-on-surface-variant transition hover:bg-surface-container-high hover:text-on-surface active:bg-surface-container-highest">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @else
                <span aria-disabled="true" aria-label="Berikutnya" class="inline-flex h-11 w-11 items-center justify-center rounded-full text-on-surface-variant/40">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
