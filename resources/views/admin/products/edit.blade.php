@extends('layouts.admin')

@section('title', '제품 수정')

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
            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900">제품 수정</h1>
            <p class="text-gray-600 mt-1">{{ $product->name }}</p>
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

    <form action="{{ route('admin.products.update', $product) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-6">기본 정보</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">제품 코드</label>
                    <input type="text" name="code" value="{{ old('code', $product->code) }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">제품명</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">브랜드</label>
                    <input type="text" name="brand" value="{{ old('brand', $product->brand) }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">카테고리</label>
                    <input type="text" name="category" value="{{ old('category', $product->category) }}" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                               value="{{ old("base_curve.$key.$i", $product->base_curve[$key][$i] ?? 0) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        @if($product->qr_path)
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">QR 코드</h2>
            <div class="flex items-start gap-6">
                <img src="{{ asset('storage/' . $product->qr_path) }}" alt="QR Code" class="w-32 h-32 rounded-lg border border-gray-200">
                <div>
                    <p class="text-sm text-gray-600 mb-2">
                        <span class="text-gray-500">URL:</span>
                        <code class="bg-gray-100 px-2 py-1 rounded text-sm ml-1">{{ config('app.url') }}/p/{{ $product->code }}</code>
                    </p>
                    <a href="{{ asset('storage/' . $product->qr_path) }}" download
                       class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        QR 코드 다운로드
                    </a>
                </div>
            </div>
        </div>
        @endif

        <div class="flex gap-4">
            <button type="submit"
                    class="flex-1 py-3 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-colors">
                수정 완료
            </button>
            <a href="{{ route('admin.products.index') }}"
               class="px-8 py-3 border-2 border-gray-200 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-colors text-center">
                취소
            </a>
        </div>
    </form>
</div>
@endsection
