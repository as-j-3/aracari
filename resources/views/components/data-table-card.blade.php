{{--
    Aracari Data Table Card
    Refreshable card wrapping the search bar + table + pagination.
    The id="data-table-card" is what aracari.js refreshes after CRUD.

    Search mode (DECLARATIVE — engine lives once in aracari.js):
      :searchMode="'server'"  → debounced → URL update → refreshRegion (default)
      :searchMode="'client'"   → local row filter via <td data-searchable> cells
      Use "server" when the controller paginates or data is large.
      Use "client"  when the controller loads all rows (no pagination).

    To mark a cell as searchable (client mode only):
      <td data-searchable>{{ $record->name }}</td>
    Action buttons / images / IDs / icons stay unmarked → excluded from search.

    Usage:
      <x-data-table-card :searchable="true" :searchMode="'server'" searchPlaceholder="ابحث عن دولة...">
        <x-slot name="thead">
          <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">#</th>
          <th class="px-6 py-4 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">الدولة</th>
        </x-slot>
        <x-slot name="rows">
          @foreach($countries as $country)
            <tr>...</tr>
          @endforeach
        </x-slot>
        <x-slot name="footer">
          {{ $countries->links() }}
        </x-slot>
      </x-data-table-card>
--}}
@props([
    'searchable' => true,
    'searchMode' => 'server', // 'server' (default) | 'client'
    'searchPlaceholder' => 'ابحث...',
    'searchValue' => null,
    'showReset' => true,
    'footerInfo' => null, // e.g. "عرض 5 من 20 سجل"
])

<div
    id="data-table-card"
    data-search-mode="{{ $searchMode }}"
    class="bg-white/80 backdrop-blur-xl border border-slate-200/70 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/20 max-w-full"
>
    @if($searchable)
        <div class="p-5 border-b border-slate-200/70 bg-white/40 backdrop-blur flex flex-col sm:flex-row gap-4 items-center justify-between">
            <form id="aracari-search-form" class="relative w-full sm:w-80">
                <i data-lucide="search" class="w-4 h-4 absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none"></i>
                <input
                    type="text"
                    id="aracari-search-input"
                    name="search"
                    value="{{ $searchValue ?? request('search') }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full h-11 bg-white/70 border border-slate-200 rounded-2xl pr-11 pl-4 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition-all duration-300"
                >
            </form>
            <div class="flex items-center gap-3 w-full sm:w-auto">
                @if($showReset && ($searchMode === 'client' || request('search') || request()->query()))
                    <button
                        id="aracari-search-reset"
                        type="button"
                        class="h-11 px-4 bg-rose-50/80 border border-rose-200 text-rose-600 rounded-xl font-bold text-sm hover:bg-rose-500 hover:text-white hover:border-rose-500 transition-all flex items-center gap-1.5 flex-shrink-0"
                    >
                        <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                        <span class="hidden sm:inline">إعادة تعيين</span>
                    </button>
                @endif
                {{-- Optional extra filters slot (e.g. status dropdown) --}}
                {{ $filters ?? '' }}
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="overflow-x-auto min-w-0">
        <table class="w-full min-w-[640px]">
            <thead class="bg-slate-50/70 border-b border-slate-200/70">
                {{ $thead }}
            </thead>
            <tbody class="divide-y divide-slate-100">
                @isset($rows)
                    {{ $rows }}
                @endisset
            </tbody>
        </table>
    </div>

    {{-- Footer + Pagination --}}
    @if($footerInfo || (isset($footer) && $footer->isNotEmpty()))
        <div class="px-6 py-4 border-t border-slate-200/70 bg-slate-50/40 flex flex-col sm:flex-row items-center justify-between gap-3">
            @if($footerInfo)
                <p data-footer-info class="text-[10px] font-bold text-slate-400">{{ $footerInfo }}</p>
            @endif
            @isset($footer)
                <div data-pagination-wrap class="flex items-center gap-1.5 flex-wrap">{{ $footer }}</div>
            @endisset
        </div>
    @endif
</div>
