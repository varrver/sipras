<main>
    @include('layouts.partials.page-heading')

    @if (session('success'))
        {{-- Auto-dismisses after 5 s with a 300 ms fade: role="alert" announces
             it on arrival, so removing it visually later cannot surprise a
             screen reader. Dismissal is Alpine-progressive — without JS the
             flash simply stays, it must never depend on the script having
             booted (so no x-cloak here, unlike the admin form panels). --}}
        {{-- `id="flash-success"` is the save-flow's anchor: status saves
             redirect here (….#flash-success) so the post-save view starts at
             the confirmation above the changed record, not at the page top. --}}
        <div id="flash-success" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-5 mb-5"
            x-data="{ gone: false }"
            x-init="setTimeout(() => gone = true, 5000)"
            x-show="!gone"
            x-transition:leave="transition ease-out duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <div class="flex items-center gap-2.5 rounded-xl bg-success-container text-on-success-container px-4 py-3 text-sm font-medium" role="alert">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-5 mb-5">
            <div class="flex items-center gap-2.5 rounded-xl bg-error-container text-on-error-container px-4 py-3 text-sm font-medium" role="alert">
                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{ $slot }}
</main>