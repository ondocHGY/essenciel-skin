@props(['model' => null, 'fields' => [], 'prefix' => 'translations'])

@php
    $locales = [
        'ko' => ['label' => '한국어', 'flag' => "\xF0\x9F\x87\xB0\xF0\x9F\x87\xB7"],
        'en' => ['label' => 'English', 'flag' => "\xF0\x9F\x87\xBA\xF0\x9F\x87\xB8"],
        'ja' => ['label' => '日本語', 'flag' => "\xF0\x9F\x87\xAF\xF0\x9F\x87\xB5"],
        'zh' => ['label' => '中文', 'flag' => "\xF0\x9F\x87\xA8\xF0\x9F\x87\xB3"],
        'vi' => ['label' => 'Tiếng Việt', 'flag' => "\xF0\x9F\x87\xBB\xF0\x9F\x87\xB3"],
        'ar' => ['label' => 'العربية', 'flag' => "\xF0\x9F\x87\xB8\xF0\x9F\x87\xA6"],
    ];
    $translations = $model ? ($model->translations ?? []) : [];
@endphp

<div x-data="{ activeLang: 'ko' }">
    {{-- Language Tab Bar --}}
    <div class="flex flex-wrap gap-1 mb-4 border-b border-gray-200 pb-2">
        @foreach ($locales as $code => $info)
            <button type="button"
                    @click="activeLang = '{{ $code }}'"
                    :class="activeLang === '{{ $code }}'
                        ? 'bg-blue-50 text-blue-700 border-blue-300'
                        : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors">
                {{ $info['flag'] }} {{ $info['label'] }}
                @if ($code === 'ko')
                    <span class="text-xs text-gray-400 ml-1">(원본)</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Korean Tab (original fields) --}}
    <div x-show="activeLang === 'ko'">
        {{ $slot }}
    </div>

    {{-- Translation Tabs --}}
    @foreach ($locales as $code => $info)
        @if ($code !== 'ko')
            <div x-show="activeLang === '{{ $code }}'" x-cloak>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-700">
                    {{ $info['flag'] }} <strong>{{ $info['label'] }}</strong> 번역을 입력하세요. 비워두면 한국어 원본이 표시됩니다.
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($fields as $field)
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">
                                {{ $field['label'] }} <span class="text-gray-400">({{ $info['label'] }})</span>
                            </label>
                            <input type="text"
                                   name="{{ $prefix }}[{{ $code }}][{{ $field['name'] }}]"
                                   value="{{ old("{$prefix}.{$code}.{$field['name']}", $translations[$code][$field['name']] ?? '') }}"
                                   placeholder="{{ $field['placeholder'] ?? '' }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endforeach
</div>
