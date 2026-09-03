{{--
    Aracari Empty State
    Centered empty/error state with icon, title, message, and optional action slot.

    Usage:
      <x-empty-state icon="inbox" title="لا توجد بيانات" message="لم يتم العثور على سجلات">
        <x-slot name="action">
          <button data-create-modal="country-modal" class="...">إضافة</button>
        </x-slot>
      </x-empty-state>
--}}
@props([
    'icon' => 'inbox',
    'title' => 'لا توجد بيانات',
    'message' => 'لم يتم العثور على أي سجلات مطابقة.',
    'colspan' => 5,
])

<td colspan="{{ $colspan }}" class="px-6 py-16 text-center">
    <div class="w-20 h-20 rounded-full bg-slate-50 mx-auto flex items-center justify-center text-slate-300 mb-4">
        <i data-lucide="{{ $icon }}" class="w-9 h-9"></i>
    </div>
    <h3 class="text-lg font-bold text-slate-700 mb-1">{{ $title }}</h3>
    <p class="text-slate-400 text-sm mb-4">{{ $message }}</p>
    @if($slot->isNotEmpty())
        <div class="mt-2">{{ $slot }}</div>
    @endif
</td>
