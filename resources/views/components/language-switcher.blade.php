@php
    $currentLocale = app()->getLocale();
    $locales = [
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
        'zh' => '中文',
        'vi' => 'Tiếng Việt',
        'ar' => 'العربية',
    ];

    // Build URL for each locale from current URL
    $currentUrl = request()->url();
    $currentPath = request()->path();

    // Strip existing locale prefix if present
    $availableLocales = config('app.available_locales', []);
    $pathSegments = explode('/', $currentPath);
    $basePath = $currentPath;
    if (count($pathSegments) > 0 && in_array($pathSegments[0], $availableLocales)) {
        array_shift($pathSegments);
        $basePath = implode('/', $pathSegments);
    }
@endphp

<div x-data="{ langOpen: false }" class="relative">
    <button @click="langOpen = !langOpen" class="flex items-center gap-1 text-white text-xs font-medium px-2 py-1 rounded hover:bg-white/10 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
        </svg>
        <span>{{ $locales[$currentLocale] ?? $currentLocale }}</span>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div x-show="langOpen" x-cloak @click.away="langOpen = false"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ app()->getLocale() === 'ar' ? 'left-0' : 'right-0' }} mt-1 w-32 bg-white rounded-lg shadow-lg border border-gray-100 py-1 z-[80]">
        @foreach($locales as $code => $label)
            @php
                if ($code === 'ko') {
                    $url = url('/' . $basePath);
                } else {
                    $url = url('/' . $code . '/' . $basePath);
                }
            @endphp
            <a href="{{ $url }}"
               class="block px-3 py-2 text-sm hover:bg-gray-50 transition-colors {{ $code === $currentLocale ? 'text-blue-600 font-semibold bg-blue-50/50' : 'text-gray-700' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>
</div>
