{{-- Paginación con el estilo del sistema (no depende de Tailwind/Bootstrap) --}}
@if($paginator->hasPages())
@php
    $current = $paginator->currentPage();
    $last    = $paginator->lastPage();
    $window  = 1; // páginas a cada lado de la actual
    $start   = max(1, $current - $window);
    $end     = min($last, $current + $window);
@endphp
<nav class="pagination" role="navigation" aria-label="Paginación">
    <div class="pagination-info">
        Mostrando <strong>{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</strong>
        de <strong>{{ $paginator->total() }}</strong>
    </div>
    <div class="pagination-controls">
        {{-- Anterior --}}
        @if($paginator->onFirstPage())
            <span class="pg-btn pg-disabled" aria-disabled="true">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10 4l-4 4 4 4"/></svg>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pg-btn" rel="prev" aria-label="Anterior">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M10 4l-4 4 4 4"/></svg>
            </a>
        @endif

        {{-- Primera página + elipsis --}}
        @if($start > 1)
            <a href="{{ $paginator->url(1) }}" class="pg-btn">1</a>
            @if($start > 2)<span class="pg-ellipsis">…</span>@endif
        @endif

        {{-- Ventana de páginas --}}
        @for($page = $start; $page <= $end; $page++)
            @if($page == $current)
                <span class="pg-btn pg-active" aria-current="page">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="pg-btn">{{ $page }}</a>
            @endif
        @endfor

        {{-- Elipsis + última página --}}
        @if($end < $last)
            @if($end < $last - 1)<span class="pg-ellipsis">…</span>@endif
            <a href="{{ $paginator->url($last) }}" class="pg-btn">{{ $last }}</a>
        @endif

        {{-- Siguiente --}}
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="pg-btn" rel="next" aria-label="Siguiente">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 4l4 4-4 4"/></svg>
            </a>
        @else
            <span class="pg-btn pg-disabled" aria-disabled="true">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M6 4l4 4-4 4"/></svg>
            </span>
        @endif
    </div>
</nav>
@endif
