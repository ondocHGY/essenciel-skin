@props([
    'label' => null,
    'name',
    'value' => null,
    'required' => false,
    'disabled' => false,
    'hint' => null,
    'options' => [],
    'placeholder' => '선택하세요',
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

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if($required) required @endif
        @if($disabled) disabled @endif
        {{ $attributes->merge([
            'class' => 'w-full px-4 py-3 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors ' .
                ($errors->has($name) ? 'border-red-500 bg-red-50' : 'border-gray-300') .
                ($disabled ? ' bg-gray-100 cursor-not-allowed' : '')
        ]) }}
    >
        @if($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected(old($name, $value) == $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        @endif
    </select>

    @if($hint && !$errors->has($name))
        <p class="text-xs text-gray-400 mt-1">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
