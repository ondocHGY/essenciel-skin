@props([
    'headers' => [],
    'striped' => false,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm overflow-hidden']) }}>
    <div class="overflow-x-auto">
        <table class="w-full">
            @if(count($headers) > 0)
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    @foreach($headers as $header)
                        @php
                            $align = $header['align'] ?? 'left';
                            $hidden = $header['hidden'] ?? null;
                            $alignClass = match($align) {
                                'center' => 'text-center',
                                'right' => 'text-right',
                                default => 'text-left',
                            };
                            $hiddenClass = $hidden ? match($hidden) {
                                'sm' => 'hidden sm:table-cell',
                                'md' => 'hidden md:table-cell',
                                'lg' => 'hidden lg:table-cell',
                                default => '',
                            } : '';
                        @endphp
                        <th class="px-6 py-4 {{ $alignClass }} text-xs font-semibold text-gray-500 uppercase tracking-wider {{ $hiddenClass }}">
                            {{ $header['label'] ?? $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            @endif
            <tbody class="divide-y divide-gray-200">
                {{ $slot }}
            </tbody>
        </table>
    </div>
</div>
