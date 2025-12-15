@extends('layouts.admin')

@section('title', '제품 추가')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- 페이지 헤더 -->
    <div class="flex items-center gap-3 mb-8">
        <a href="{{ route('admin.products.index') }}" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">제품 추가</h1>
            <p class="text-gray-600 mt-1">새로운 제품을 등록합니다</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc pl-5 space-y-1">
            @foreach($errors->all() as $error)
                <li class="text-sm">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">기본 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">제품 코드</label>
                    <input type="text" name="code" value="{{ old('code') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="PROD-001">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">제품명</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">브랜드</label>
                    <input type="text" name="brand" value="{{ old('brand') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">카테고리</label>
                    <input type="text" name="category" value="{{ old('category') }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="세럼, 크림, 에센스 등">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-2">효과 곡선 (Base Curve)</h2>
            <p class="text-sm text-gray-500 mb-6">각 항목별 1주, 2주, 4주, 8주, 12주 시점의 예상 효과(0-100)</p>

            @foreach(['moisture' => '수분', 'elasticity' => '탄력', 'tone' => '피부톤', 'pore' => '모공', 'wrinkle' => '주름'] as $key => $label)
            <div class="mb-6 last:mb-0">
                <label class="block text-sm font-medium text-gray-700 mb-3">{{ $label }}</label>
                <div class="grid grid-cols-5 gap-3">
                    @foreach([0, 1, 2, 3, 4] as $i)
                    <div>
                        <label class="text-xs text-gray-400 block mb-1">{{ [1, 2, 4, 8, 12][$i] }}주</label>
                        <input type="number" name="base_curve[{{ $key }}][]" min="0" max="100" required
                               value="{{ old("base_curve.$key.$i", [10, 25, 40, 60, 80][$i]) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="flex gap-4">
            <button type="submit"
                    class="flex-1 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                제품 등록
            </button>
            <a href="{{ route('admin.products.index') }}"
               class="px-8 py-3 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors text-center">
                취소
            </a>
        </div>
    </form>
</div>
@endsection
