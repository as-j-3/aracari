{{--
    Aracari Pagination
    Custom-styled pagination that refreshes the table card without full reload.

    Usage:
      <x-pagination :paginator="$countries" />
--}}
@props(['paginator'])

@php
    if (!($paginator instanceof \Illuminate\Pagination\AbstractCursorPaginator) && method_exists($paginator, 'onEachSide')) {
        $paginator->onEachSide(1);
    }
    $elements = $paginator instanceof \Illuminate\Pagination\AbstractCursorPaginator ? [] : $paginator->links()->elements ?? [];
    // build manually to control link attributes
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $pages = [];
    if ($lastPage <= 7) {
        for ($i = 1; $i <= $lastPage; $i++) $pages[] = $i;
    } else {
        $pages[] = 1;
        if ($currentPage > 3) $pages[] = '...';
        $start = max(2, $currentPage - 1);
        $end = min($lastPage - 1, $currentPage + 1);
        for ($i = $start; $i <= $end; $i++) $pages[] = $i;
        if ($currentPage < $lastPage - 2) $pages[] = '...';
        $pages[] = $lastPage;
    }
@endphp

@if($paginator->hasPages())
    @if(!$paginator->onFirstPage())
        <a
            href="{{ $paginator->previousPageUrl() }}"
            data-partial-link
            class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center text-xs font-bold transition-all"
            aria-label="السابق"
            rel="prev"
        >
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </a>
    @else
        <span class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-300 flex items-center justify-center text-xs font-bold cursor-not-allowed" aria-disabled="true">
            <i data-lucide="chevron-right" class="w-4 h-4"></i>
        </span>
    @endif

    @foreach($pages as $page)
        @if($page === '...')
            <span class="w-8 h-8 flex items-center justify-center text-slate-400 text-xs font-bold">…</span>
        @elseif($page == $currentPage)
            <span class="w-8 h-8 rounded-lg bg-teal-500 text-white shadow-md shadow-teal-500/30 flex items-center justify-center text-xs font-bold" aria-current="page">{{ $page }}</span>
        @else
            <a
                href="{{ $paginator->url($page) }}"
                data-partial-link
                class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center text-xs font-bold transition-all"
            >{{ $page }}</a>
        @endif
    @endforeach

    @if($paginator->hasMorePages())
        <a
            href="{{ $paginator->nextPageUrl() }}"
            data-partial-link
            class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-500 hover:bg-slate-50 flex items-center justify-center text-xs font-bold transition-all"
            aria-label="التالي"
            rel="next"
        >
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </a>
    @else
        <span class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-300 flex items-center justify-center text-xs font-bold cursor-not-allowed" aria-disabled="true">
            <i data-lucide="chevron-left" class="w-4 h-4"></i>
        </span>
    @endif
@endif
