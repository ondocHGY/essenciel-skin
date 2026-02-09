<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncLog;
use App\Models\ProductReviewSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScraperController extends Controller
{
    /**
     * review-collector API base URL
     */
    private function apiUrl(): string
    {
        return rtrim(config('services.review_collector.url', 'http://localhost:8002'), '/');
    }

    /**
     * 스크래퍼 관리 페이지
     */
    public function index(Request $request)
    {
        // 실행 기록 조회
        $query = SyncLog::with('reviewSource')->latest();

        if ($request->filled('platform')) {
            $query->platform($request->platform);
        }
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        $syncLogs = $query->paginate(20)->withQueryString();

        // 통계
        $stats = [
            'total' => SyncLog::count(),
            'success' => SyncLog::where('status', 'success')->count(),
            'failed' => SyncLog::where('status', 'failed')->count(),
            'running' => SyncLog::where('status', 'running')->count(),
        ];

        // 쿠키 상태 (review-collector API 호출)
        $cookies = $this->fetchCookieStatus();

        // 플랫폼 목록
        $platforms = ProductReviewSource::$platforms;

        // 서비스 상태
        $serviceStatus = $this->checkServiceHealth();

        return view('admin.scraper.index', compact(
            'syncLogs', 'stats', 'cookies', 'platforms', 'serviceStatus'
        ));
    }

    /**
     * 수동 동기화 실행
     */
    public function sync(Request $request)
    {
        $request->validate([
            'platform' => 'nullable|string|max:50',
            'source_id' => 'nullable|integer',
        ]);

        try {
            $body = [];
            if ($request->filled('platform')) {
                $body['platform'] = $request->platform;
            }
            if ($request->filled('source_id')) {
                $body['source_id'] = (int) $request->source_id;
            }

            $response = Http::timeout(120)
                ->post($this->apiUrl() . '/api/reviews/sync', $body);

            if ($response->successful()) {
                $data = $response->json();
                $message = $data['message'] ?? '동기화 완료';
                $added = $data['reviews_added'] ?? 0;
                $updated = $data['reviews_updated'] ?? 0;

                return back()->with('success', "{$message} (추가: {$added}, 업데이트: {$updated})");
            }

            $error = $response->json('detail') ?? $response->body();
            return back()->with('error', "동기화 실패: {$error}");

        } catch (\Exception $e) {
            Log::error('동기화 요청 실패: ' . $e->getMessage());
            return back()->with('error', '동기화 서비스에 연결할 수 없습니다: ' . $e->getMessage());
        }
    }

    /**
     * 쿠키 파일 업로드
     */
    public function uploadCookie(Request $request, string $platform)
    {
        $request->validate([
            'cookie_file' => 'required|file|max:5120',
        ]);

        try {
            $file = $request->file('cookie_file');

            $response = Http::timeout(30)
                ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
                ->post($this->apiUrl() . "/api/cookies/{$platform}");

            if ($response->successful()) {
                return back()->with('success', "{$platform} 쿠키가 업로드되었습니다.");
            }

            $error = $response->json('detail') ?? $response->body();
            return back()->with('error', "쿠키 업로드 실패: {$error}");

        } catch (\Exception $e) {
            Log::error("쿠키 업로드 실패 ({$platform}): " . $e->getMessage());
            return back()->with('error', '쿠키 업로드 실패: ' . $e->getMessage());
        }
    }

    /**
     * 쿠키 파일 삭제
     */
    public function deleteCookie(string $platform)
    {
        try {
            $response = Http::timeout(10)
                ->delete($this->apiUrl() . "/api/cookies/{$platform}");

            if ($response->successful()) {
                return back()->with('success', "{$platform} 쿠키가 삭제되었습니다.");
            }

            $error = $response->json('detail') ?? $response->body();
            return back()->with('error', "쿠키 삭제 실패: {$error}");

        } catch (\Exception $e) {
            Log::error("쿠키 삭제 실패 ({$platform}): " . $e->getMessage());
            return back()->with('error', '쿠키 삭제 실패: ' . $e->getMessage());
        }
    }

    /**
     * review-collector에서 쿠키 상태 조회
     */
    private function fetchCookieStatus(): array
    {
        try {
            $response = Http::timeout(5)->get($this->apiUrl() . '/api/cookies');

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::warning('쿠키 상태 조회 실패: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * 서비스 헬스체크
     */
    private function checkServiceHealth(): array
    {
        try {
            $response = Http::timeout(3)->get($this->apiUrl() . '/health');

            if ($response->successful()) {
                return [
                    'online' => true,
                    'message' => '정상 동작중',
                ];
            }
        } catch (\Exception $e) {
            // 연결 실패
        }

        return [
            'online' => false,
            'message' => '연결 불가',
        ];
    }
}
