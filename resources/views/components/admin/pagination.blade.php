@props(['paginator'])

@php
    $paginator->withQueryString();
    $startPage = max(1, $paginator->currentPage() - 2);
    $endPage = min($paginator->lastPage(), $paginator->currentPage() + 2);
@endphp

@if ($paginator->hasPages())
    <nav class="flex flex-wrap items-center justify-between gap-3" aria-label="Pagination">
        <p class="text-xs text-slate-500">
            Showing {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </p>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-300" aria-disabled="true">Previous</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Previous</a>
            @endif

            @foreach ($paginator->getUrlRange($startPage, $endPage) as $page => $url)
                @if ($page === $paginator->currentPage())
                    <span class="flex size-8 items-center justify-center rounded-lg bg-indigo-600 text-xs font-semibold text-white" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="flex size-8 items-center justify-center rounded-lg text-xs font-semibold text-slate-600 hover:bg-slate-100">{{ $page }}</a>
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100">Next</a>
            @else
                <span class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-300" aria-disabled="true">Next</span>
            @endif
        </div>
    </nav>
@endif
