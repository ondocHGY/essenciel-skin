@extends('layouts.admin')

@section('title', 'QR 코드 생성')

@section('content')
<div class="max-w-2xl mx-auto">
    {{-- 페이지 헤더 --}}
    <x-page-header title="QR 코드 생성" description="원하는 URL로 QR 코드를 생성합니다" />

    {{-- 제품별 다국어 QR 생성 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6" x-data="localeQrGenerator()">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">제품별 다국어 QR 생성</h3>

        <div class="space-y-5">
            {{-- 제품 선택 --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">제품 선택</label>
                <select x-model="productId"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <option value="">-- 제품을 선택하세요 --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->code }})</option>
                    @endforeach
                </select>
            </div>

            {{-- 언어 체크박스 --}}
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">언어 선택</label>
                    <label class="inline-flex items-center gap-1.5 text-sm text-gray-500 cursor-pointer">
                        <input type="checkbox" @change="toggleAll($event.target.checked)"
                               :checked="locales.length === 6"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>전체선택</span>
                    </label>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <template x-for="loc in allLocales" :key="loc.code">
                        <label class="flex items-center gap-2 px-3 py-2.5 border rounded-lg cursor-pointer transition-colors"
                               :class="locales.includes(loc.code) ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'">
                            <input type="checkbox" :value="loc.code"
                                   :checked="locales.includes(loc.code)"
                                   @change="toggleLocale(loc.code)"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm font-medium" x-text="loc.label"></span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- 생성 버튼 --}}
            <button @click="generate()" :disabled="!productId || locales.length === 0 || loading"
                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                <template x-if="loading">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </template>
                <template x-if="!loading">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                    </svg>
                </template>
                <span x-text="loading ? '생성 중...' : '다국어 QR 코드 생성'"></span>
            </button>
        </div>

        {{-- 결과 그리드 --}}
        <div x-show="results.length > 0" x-cloak class="mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">생성된 QR 코드</h3>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <template x-for="item in results" :key="item.locale">
                    <div class="border border-gray-200 rounded-xl p-4 flex flex-col items-center gap-3">
                        <span class="text-sm font-semibold text-gray-700" x-text="item.label"></span>
                        <img :src="item.url" :alt="'QR ' + item.label" class="w-40 h-40">
                        <p class="text-xs text-gray-400 text-center break-all leading-tight" x-text="item.embedded_url"></p>
                        <a :href="item.url" :download="'qr_' + item.locale + '.png'"
                           class="inline-flex items-center gap-1.5 text-sm bg-green-600 hover:bg-green-700 text-white font-medium py-1.5 px-3 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            다운로드
                        </a>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- URL로 QR 생성 --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">URL로 QR 생성</h3>
        <form id="qrForm" class="space-y-5">
            @csrf
            <div>
                <label for="url" class="block text-sm font-medium text-gray-700 mb-2">URL</label>
                <input type="url" id="url" name="url" required
                       placeholder="https://example.com"
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                <p class="mt-1.5 text-sm text-gray-500">QR 코드로 변환할 웹 주소를 입력하세요</p>
            </div>

            <div>
                <label for="filename" class="block text-sm font-medium text-gray-700 mb-2">파일명 (선택)</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="filename" name="filename"
                           placeholder="my-qrcode"
                           class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    <span class="text-gray-500">.png</span>
                </div>
                <p class="mt-1.5 text-sm text-gray-500">영문, 숫자, 언더스코어(_), 하이픈(-)만 사용 가능. 비워두면 자동 생성됩니다</p>
            </div>

            <button type="submit" id="generateBtn"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
                <span>QR 코드 생성</span>
            </button>
        </form>

        {{-- 생성된 QR 코드 결과 --}}
        <div id="resultSection" class="hidden mt-8 pt-6 border-t border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">생성된 QR 코드</h3>

            <div class="flex flex-col items-center gap-4">
                <div class="bg-white p-4 border-2 border-gray-200 rounded-xl">
                    <img id="qrImage" src="" alt="QR Code" class="w-64 h-64">
                </div>

                <div class="flex items-center gap-3">
                    <a id="downloadBtn" href="" download
                       class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white font-medium py-2.5 px-5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        다운로드
                    </a>
                    <button onclick="copyImageUrl()"
                            class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2.5 px-5 rounded-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        URL 복사
                    </button>
                </div>

                <p id="imageUrlText" class="text-sm text-gray-500 text-center break-all max-w-md"></p>
            </div>
        </div>
    </div>
</div>

<script>
function localeQrGenerator() {
    return {
        productId: '',
        locales: [],
        loading: false,
        results: [],
        allLocales: [
            { code: 'ko', label: '한국어' },
            { code: 'en', label: 'English' },
            { code: 'ja', label: '日本語' },
            { code: 'zh', label: '中文' },
            { code: 'vi', label: 'Tiếng Việt' },
            { code: 'ar', label: 'العربية' },
        ],
        toggleLocale(code) {
            const idx = this.locales.indexOf(code);
            if (idx === -1) this.locales.push(code);
            else this.locales.splice(idx, 1);
        },
        toggleAll(checked) {
            this.locales = checked ? this.allLocales.map(l => l.code) : [];
        },
        async generate() {
            this.loading = true;
            this.results = [];
            try {
                const res = await fetch('{{ route("admin.custom-qr.generate-locale") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: this.productId,
                        locales: this.locales,
                    })
                });
                const data = await res.json();
                if (!res.ok) throw new Error(data.message || Object.values(data.errors || {}).flat().join('\n'));
                if (data.success) this.results = data.results;
            } catch (e) {
                alert(e.message || 'QR 코드 생성에 실패했습니다.');
            } finally {
                this.loading = false;
            }
        }
    };
}

document.getElementById('qrForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('generateBtn');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = `
        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>생성 중...</span>
    `;

    try {
        const formData = new FormData(this);
        const response = await fetch('{{ route("admin.custom-qr.generate") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                url: formData.get('url'),
                filename: formData.get('filename')
            })
        });

        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join('\n'));
        }

        if (data.success) {
            document.getElementById('qrImage').src = data.url;
            document.getElementById('downloadBtn').href = data.url;
            document.getElementById('imageUrlText').textContent = data.url;
            document.getElementById('resultSection').classList.remove('hidden');

            document.getElementById('resultSection').scrollIntoView({ behavior: 'smooth' });
        }
    } catch (error) {
        alert(error.message || 'QR 코드 생성에 실패했습니다.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
});

function copyImageUrl() {
    const url = document.getElementById('imageUrlText').textContent;
    navigator.clipboard.writeText(url).then(() => {
        alert('URL이 복사되었습니다.');
    });
}
</script>
@endsection