{{--
    Aracari Delete Confirmation Modal
    Unified delete confirmation modal (replaces window.confirm).
    Wired to aracari.js openDeleteConfirm() / ajaxSubmit().

    Just include ONCE per page (typically at the bottom):
      <x-delete-confirm />

    To trigger:
      <button data-delete-url="{{ route('admin.countries.destroy', $country) }}" data-delete-label="{{ $country->name }}">...</button>
--}}
<x-modal id="aracari-delete-modal" title="تأكيد الحذف" variant="view" icon="trash-2" :static="true" maxWidth="max-w-md">
    <form
        id="aracari-delete-form"
        method="POST"
        data-aracari-ajax
        data-success-message="تم الحذف بنجاح"
        class="space-y-5"
    >
        @csrf
        <input type="hidden" name="_method" value="DELETE">
        {{-- banner message slot --}}
        <div class="aracari-error-banner hidden p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 text-xs flex items-start gap-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
            <span data-banner-message>حدث خطأ أثناء الحذف.</span>
        </div>

        {{-- Warning icon + message --}}
        <div class="text-center space-y-3 py-2">
            <div class="w-16 h-16 rounded-full bg-rose-50 border border-rose-100 mx-auto flex items-center justify-center">
                <i data-lucide="trash-2" class="w-8 h-8 text-rose-500"></i>
            </div>
            <h3 class="text-base font-bold text-slate-800" data-delete-title>تأكيد الحذف</h3>
            <p class="text-sm text-slate-500 leading-relaxed" data-delete-message>
                هل أنت متأكد من حذف هذا العنصر؟ لا يمكن التراجع عن هذا الإجراء.
            </p>
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                <i data-lucide="tag" class="w-3.5 h-3.5"></i>
                <span data-delete-label>—</span>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex gap-3 pt-1">
            <button
                type="button"
                data-close-modal
                class="flex-1 bg-[#f3f3f6] hover:bg-[#eeeef0] text-gray-600 font-bold py-3 px-4 rounded-xl text-xs transition-all flex items-center justify-center gap-2"
            >
                <i data-lucide="x" class="w-4 h-4"></i>
                إلغاء
            </button>
            <button
                type="submit"
                class="flex-[2] text-white font-bold py-3 px-4 rounded-xl text-xs transition-all shadow-md flex items-center justify-center gap-2 bg-rose-600 hover:bg-rose-700 active:scale-95"
            >
                <i data-lucide="trash-2" class="w-4 h-4"></i>
                <span>نعم، احذف</span>
            </button>
        </div>
    </form>
</x-modal>
