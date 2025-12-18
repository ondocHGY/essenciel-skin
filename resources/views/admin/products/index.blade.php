@extends('layouts.admin')

@section('title', '제품 관리')

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="제품 관리" :description="'총 ' . $products->total() . '개의 제품이 등록되어 있습니다'">
        <x-button :href="route('admin.products.create')" variant="primary" size="md">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            제품 추가
        </x-button>
    </x-page-header>

    {{-- 플래시 메시지 --}}
    <x-flash-messages />

    {{-- 제품 테이블 --}}
    <x-table :headers="[
        ['label' => '코드'],
        ['label' => '제품 정보'],
        ['label' => '카테고리', 'hidden' => 'lg'],
        ['label' => '분석 수', 'hidden' => 'md'],
        ['label' => 'QR 코드'],
        ['label' => '관리', 'align' => 'right'],
    ]">
        @forelse($products as $product)
        <tr class="hover:bg-gray-50 transition-colors">
            <td class="px-6 py-4">
                <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded">{{ $product->code }}</span>
            </td>
            <td class="px-6 py-4">
                <p class="font-medium text-gray-900">{{ $product->name }}</p>
                <p class="text-sm text-gray-500">{{ $product->brand }}</p>
            </td>
            <td class="px-6 py-4 text-sm text-gray-600 hidden lg:table-cell">{{ $product->category }}</td>
            <td class="px-6 py-4 hidden md:table-cell">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    {{ $product->analysisResults()->count() }}건
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex flex-col gap-1">
                    <div class="flex items-center gap-1">
                        <input type="text" readonly value="{{ config('app.url') }}/p/{{ $product->code }}"
                               class="text-xs bg-gray-50 border border-gray-200 rounded px-2 py-1 w-48 text-gray-600 cursor-pointer"
                               onclick="this.select(); document.execCommand('copy'); alert('URL이 복사되었습니다.');">
                        <button onclick="copyUrl('{{ config('app.url') }}/p/{{ $product->code }}')"
                                class="text-gray-400 hover:text-gray-600" title="URL 복사">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($product->qr_path)
                            <a href="{{ asset('storage/' . $product->qr_path) }}" target="_blank"
                               class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 text-xs font-medium">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                </svg>
                                보기
                            </a>
                        @endif
                        <button onclick="generateQR({{ $product->id }})"
                                class="inline-flex items-center gap-1 text-gray-500 hover:text-blue-600 text-xs font-medium">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            {{ $product->qr_path ? '재생성' : '생성' }}
                        </button>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('product.show', $product->code) }}" target="_blank"
                       class="text-gray-500 hover:text-gray-700" title="미리보기">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </a>
                    <a href="{{ route('admin.products.edit', $product) }}"
                       class="text-blue-600 hover:text-blue-700" title="수정">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    <form action="{{ route('admin.products.destroy', $product) }}" method="POST"
                          onsubmit="return confirm('정말 삭제하시겠습니까?')" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-700" title="삭제">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6">
                <x-empty-state
                    title="등록된 제품이 없습니다"
                    :actionUrl="route('admin.products.create')"
                    actionLabel="첫 제품 추가하기">
                    <x-slot:icon>
                        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </x-slot:icon>
                </x-empty-state>
            </td>
        </tr>
        @endforelse
    </x-table>

    {{-- 페이지네이션 --}}
    @if($products->hasPages())
    <div class="mt-6">
        {{ $products->links() }}
    </div>
    @endif
</div>

<script>
function generateQR(productId) {
    fetch(`/admin/products/${productId}/qr`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function copyUrl(url) {
    navigator.clipboard.writeText(url).then(() => {
        alert('URL이 복사되었습니다.');
    });
}
</script>
@endsection
