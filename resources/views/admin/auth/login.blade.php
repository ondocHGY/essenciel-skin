<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 로그인</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <x-card class="p-8" rounded="2xl">
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">관리자 로그인</h1>
                <p class="text-gray-500 text-sm mt-1">스킨케어 분석 서비스 관리</p>
            </div>

            @if($errors->any())
            <x-alert type="error" class="mb-6">
                @foreach($errors->all() as $error)
                <p class="text-sm">{{ $error }}</p>
                @endforeach
            </x-alert>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-5">
                @csrf

                <x-form-input
                    name="email"
                    type="email"
                    label="이메일"
                    :value="old('email')"
                    placeholder="admin@example.com"
                    required />

                <x-form-input
                    name="password"
                    type="password"
                    label="비밀번호"
                    placeholder="••••••••"
                    required />

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember"
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="remember" class="ml-2 text-sm text-gray-600">로그인 상태 유지</label>
                </div>

                <x-button type="submit" variant="primary" size="lg" class="w-full">
                    로그인
                </x-button>
            </form>
        </x-card>

        <p class="text-center text-gray-400 text-xs mt-6">
            &copy; {{ date('Y') }} 스킨케어 분석 서비스
        </p>
    </div>
</body>
</html>
