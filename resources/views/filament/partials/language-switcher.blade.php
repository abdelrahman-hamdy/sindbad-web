@php $currentLocale = app()->getLocale(); @endphp
<form method="POST" action="{{ route('set-locale') }}" class="flex items-center">
    @csrf
    <input type="hidden" name="locale" value="{{ $currentLocale === 'ar' ? 'en' : 'ar' }}">
    <button
        type="submit"
        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
        title="{{ $currentLocale === 'ar' ? 'Switch to English' : 'التبديل إلى العربية' }}"
    >
        @if ($currentLocale === 'ar')
            <span>EN</span>
        @else
            <span>عربي</span>
        @endif
    </button>
</form>
