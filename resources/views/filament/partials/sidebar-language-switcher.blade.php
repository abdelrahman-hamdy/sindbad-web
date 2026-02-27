@php $currentLocale = app()->getLocale(); @endphp
<div class="px-4 py-3 border-t border-gray-200 dark:border-white/10">
    <form method="POST" action="{{ route('set-locale') }}">
        @csrf
        <input type="hidden" name="locale" value="{{ $currentLocale === 'ar' ? 'en' : 'ar' }}">
        <button
            type="submit"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5 transition"
        >
            <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 01-3.827-5.802"/>
            </svg>
            <span>{{ $currentLocale === 'ar' ? __('Switch to English') : __('Switch to Arabic') }}</span>
        </button>
    </form>
</div>
