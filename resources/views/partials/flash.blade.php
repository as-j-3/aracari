{{--
    Aracari Flash Messages
    Renders Laravel session flash messages (success / error / warning / info).
    Place inside the layout right before @yield('content').

    Uses the same glassmorphic style as the design system.
--}}
@if(session('success'))
    <div class="aracari-flash aracari-flash-success mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="check-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold">{{ session('success') }}</span>
        <button type="button" class="mr-auto text-emerald-400 hover:text-emerald-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="aracari-flash aracari-flash-error mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold">{{ session('error') }}</span>
        <button type="button" class="mr-auto text-rose-400 hover:text-rose-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="aracari-flash aracari-flash-warning mb-4 p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold">{{ session('warning') }}</span>
        <button type="button" class="mr-auto text-amber-400 hover:text-amber-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
@endif

@if(session('info'))
    <div class="aracari-flash aracari-flash-info mb-4 p-4 rounded-2xl bg-blue-50 border border-blue-200 text-blue-700 flex items-center gap-3 animate-flash-in" role="alert">
        <i data-lucide="info" class="w-5 h-5 flex-shrink-0"></i>
        <span class="text-sm font-bold">{{ session('info') }}</span>
        <button type="button" class="mr-auto text-blue-400 hover:text-blue-600" onclick="this.parentElement.remove()">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
@endif

{{-- validation errors (when redirected with errors) --}}
@if($errors->any())
    <div class="aracari-flash aracari-flash-error mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700" role="alert">
        <div class="flex items-start gap-3">
            <i data-lucide="alert-circle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
            <div class="flex-1">
                <p class="text-sm font-bold mb-1">يرجى تصحيح الأخطاء التالية:</p>
                <ul class="text-xs font-medium list-disc pr-4 space-y-0.5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="text-rose-400 hover:text-rose-600" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
@endif
