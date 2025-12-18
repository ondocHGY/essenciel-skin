@props([
    'label' => null,
    'name',
    'value' => null,
    'placeholder' => '',
    'required' => false,
    'disabled' => false,
    'rows' => 3,
    'hint' => null,
])

<div>
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge([
            'class' => 'w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors resize-none ' .
                ($errors->has($name) ? 'border-red-500 bg-red-50' : 'border-gray-300') .
                ($disabled ? ' bg-gray-100 cursor-not-allowed' : '')
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if($hint && !$errors->has($name))
        <p class="text-xs text-gray-400 mt-1">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
