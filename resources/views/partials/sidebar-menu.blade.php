@if(\hexa_core\Models\Setting::isPackageEnabled('hexawebsystems/laravel-hexa-package-newsdata'))
@if(auth()->check())

@once('news-sidebar-header')
<p class="text-xs text-gray-600 uppercase tracking-wider pt-4 pb-1 px-3">News</p>
@endonce

<a href="{{ route('newsdata.index') }}"
   class="flex items-center px-3 py-2 rounded-lg text-sm {{ request()->is('raw-newsdata*') || request()->is('newsdata*') ? 'sidebar-active' : 'sidebar-hover' }}">
    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>
    NewsData
</a>

@endif
@endif
