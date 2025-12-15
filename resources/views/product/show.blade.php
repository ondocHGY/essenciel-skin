@extends('layouts.app')

@section('title', $product->name . ' - 피부 효과 분석')

@section('content')
<div class="px-4 py-6">
    {{-- 헤더 --}}
    <div class="text-center mb-8">
        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full mb-3">
            {{ $product->brand }}
        </span>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $product->name }}</h1>
        <p class="text-gray-500 text-sm">{{ $product->category }}</p>
    </div>

    {{-- 제품 효과 미리보기 --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">기대 효과</h2>
        <div class="space-y-3">
            @php
                $effectLabels = [
                    'moisture' => ['수분 공급', 'bg-blue-100 text-blue-700'],
                    'elasticity' => ['탄력 개선', 'bg-purple-100 text-purple-700'],
                    'tone' => ['피부톤 개선', 'bg-orange-100 text-orange-700'],
                    'pore' => ['모공 케어', 'bg-green-100 text-green-700'],
                    'wrinkle' => ['주름 개선', 'bg-pink-100 text-pink-700'],
                ];
            @endphp
            @foreach($product->base_curve as $key => $values)
                @if(isset($effectLabels[$key]))
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-medium {{ $effectLabels[$key][1] }}">
                            {{ $effectLabels[$key][0] }}
                        </span>
                        <div class="flex-1 mx-3">
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 {{ str_replace('text-', 'bg-', explode(' ', $effectLabels[$key][1])[0]) }}"
                                     style="width: {{ end($values) }}%"></div>
                            </div>
                        </div>
                        <span class="text-sm font-medium text-gray-600">{{ end($values) }}%</span>
                    </div>
                @endif
            @endforeach
        </div>
    </div>

    {{-- 성분 정보 --}}
    @if($product->ingredients && count($product->ingredients) > 0)
    <div class="bg-white rounded-2xl shadow-sm p-5 mb-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">주요 성분</h2>
        <div class="flex flex-wrap gap-2">
            @foreach($product->ingredients as $ingredient)
                <span class="px-3 py-1 bg-gray-100 text-gray-700 text-sm rounded-full">
                    {{ $ingredient }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- CTA 버튼 --}}
    <div class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100">
        <div class="max-w-lg mx-auto">
            <a href="{{ route('survey.index', $product->code) }}"
               class="block w-full py-4 bg-blue-600 hover:bg-blue-700 text-white text-center font-semibold rounded-xl transition-colors">
                나만의 피부 효과 분석 시작하기
            </a>
            <p class="text-center text-gray-400 text-xs mt-2">약 1분 소요</p>
        </div>
    </div>

    {{-- 하단 여백 --}}
    <div class="h-24"></div>
</div>
@endsection
