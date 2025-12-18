@props([
    'padding' => '5',
    'rounded' => 'xl',
    'shadow' => true,
])

<div {{ $attributes->merge([
    'class' => 'bg-white rounded-' . $rounded . ($shadow ? ' shadow-sm' : '') . ' p-' . $padding
]) }}>
    {{ $slot }}
</div>
