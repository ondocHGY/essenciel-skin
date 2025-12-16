@extends('layouts.admin')

@section('title', '카테고리 추가')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- 헤더 -->
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.survey-options.index') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">새 카테고리 추가</h1>
            <p class="text-gray-500">설문에 새로운 질문 카테고리를 추가합니다</p>
        </div>
    </div>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
        <ul class="list-disc list-inside">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm p-6">
        <form action="{{ route('admin.survey-options.store') }}" method="POST">
            @csrf
            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">카테고리 키 (영문 소문자, 언더스코어만)</label>
                    <input type="text" name="key" value="{{ old('key') }}" placeholder="예: skin_concern"
                           pattern="^[a-z_]+$"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    <p class="text-xs text-gray-500 mt-1">시스템에서 사용되는 고유 식별자입니다. 생성 후 변경할 수 없습니다.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">카테고리명</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="예: 피부 고민"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">설명 (선택)</label>
                    <textarea name="description" rows="2" placeholder="이 카테고리에 대한 설명을 입력하세요"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('description') }}</textarea>
                </div>

                <div class="flex gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_icon" value="1" {{ old('has_icon') ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm text-gray-700">아이콘 사용</span>
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_multiple" value="1" {{ old('is_multiple') ? 'checked' : '' }}
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-sm text-gray-700">복수 선택 가능</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 mt-8">
                <a href="{{ route('admin.survey-options.index') }}"
                   class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-center font-medium">
                    취소
                </a>
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                    카테고리 생성
                </button>
            </div>
        </form>
    </div>

    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h3 class="font-medium text-yellow-800 mb-2">참고</h3>
        <ul class="text-sm text-yellow-700 space-y-1">
            <li>- 카테고리를 생성한 후 옵션을 추가해야 설문에 표시됩니다.</li>
            <li>- 아이콘 사용을 선택하면 각 옵션에 이모지를 추가할 수 있습니다.</li>
            <li>- 복수 선택을 활성화하면 사용자가 여러 옵션을 선택할 수 있습니다.</li>
        </ul>
    </div>
</div>
@endsection
