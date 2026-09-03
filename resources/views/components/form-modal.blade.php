{{--
    Aracari Form Modal
    Full form modal wired to aracari.js ajaxSubmit.
    Wraps x-modal with: form tag, CSRF, error banner, cancel/save buttons.

    Usage:
      <x-form-modal
          id="country-modal"
          title="إضافة دولة"
          :variant="$editing ? 'edit' : 'create'"
          action="{{ $editing ? route('admin.countries.update', $country) : route('admin.countries.store') }}"
          method="{{ $editing ? 'PUT' : 'POST' }}"
          successMessage="تم حفظ الدولة بنجاح"
      >
        <x-form-field name="name" label="اسم الدولة" icon="globe" required>
          <input type="text" name="name" id="name" class="aracari-input" value="{{ old('name', $country->name ?? '') }}">
        </x-form-field>

        <x-slot name="actions">
          <button type="submit">حفظ</button>
        </x-slot>
      </x-form-modal>

    Notes:
      - The form gets data-aracari-ajax automatically.
      - Error banner is included.
      - Cancel button (data-close-modal) included by default.
      - Save button goes in the "actions" slot.
--}}
@props([
    'id' => 'aracari-form-modal',
    'title' => '',
    'subtitle' => null,
    'icon' => null,
    'variant' => 'create', // create | edit
    'action' => '',
    'method' => 'POST',
    'successMessage' => 'تم الحفظ بنجاح',
    'maxWidth' => 'max-w-lg',
])

<x-modal :id="$id" :title="$title" :subtitle="$subtitle" :icon="$icon" :variant="$variant" :maxWidth="$maxWidth">
    <form
        method="POST"
        action="{{ $action }}"
        data-aracari-ajax
        data-success-message="{{ $successMessage }}"
        class="space-y-5"
    >
        @csrf
        @if(strtoupper($method) !== 'POST')
            <input type="hidden" name="_method" value="{{ strtoupper($method) }}" data-dynamic="true">
        @endif

        {{-- Error banner (filled by aracari.js on 422) --}}
        <div class="aracari-error-banner hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span data-banner-message>يرجى تصحيح الأخطاء أدناه.</span>
        </div>

        {{-- Form fields (slot) --}}
        {{ $slot }}

        {{-- Action buttons --}}
        <div class="flex gap-3 pt-1">
            <button
                type="button"
                data-close-modal
                class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
                إلغاء
            </button>
            @isset($actions)
                {{ $actions }}
            @else
                <button
                    type="submit"
                    class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 active:scale-95 {{ $variant === 'edit' ? 'bg-amber-600 hover:bg-amber-700' : 'bg-[#115128] hover:bg-emerald-950' }}"
                >
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>{{ $variant === 'edit' ? 'حفظ التعديلات' : 'حفظ' }}</span>
                </button>
            @endisset
        </div>
    </form>
</x-modal>
