<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
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
        $query = SyncLog::with('reviewSource')->orderByDesc('started_at');

        if ($request->filled('platform')) {
            $query->platform($request->platform);
        }
        if ($request->filled('status')) {
            $query->status($request->status);
        }

        $syncLogs = $query->paginate(10)->withQueryString();

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

        // 리뷰 소스 목록
        $sources = ProductReviewSource::with('product')->orderByDesc('created_at')->paginate(10, ['*'], 'sources_page')->withQueryString();

        // 제품 목록 (소스 등록용)
        $products = Product::orderBy('name')->get();

        return view('admin.scraper.index', compact(
            'syncLogs', 'stats', 'cookies', 'platforms', 'serviceStatus', 'sources', 'products'
        ));
    }

    /**
     * 수동 동기화 실행
     */
    public function sync(Request $request)
    {
        $request->validate([
            'platform' => 'nullable|string|max:50',
        ]);

        try {
            $body = [];
            if ($request->filled('platform')) {
                $body['platform'] = $request->platform;
            }

            $response = Http::timeout(600)
                ->post($this->apiUrl() . '/api/reviews/sync', $body);

            if ($response->successful()) {
                $data = $response->json();

                // API가 플랫폼별 결과 리스트를 반환
                if (is_array($data) && isset($data[0])) {
                    $totalAdded = 0;
                    $totalUpdated = 0;
                    $messages = [];
                    foreach ($data as $result) {
                        $platform = $result['platform'] ?? '';
                        $success = $result['success'] ?? false;
                        $added = $result['reviews_added'] ?? 0;
                        $updated = $result['reviews_updated'] ?? 0;
                        $totalAdded += $added;
                        $totalUpdated += $updated;
                        $status = $success ? '성공' : '실패';
                        $messages[] = "{$platform}: {$status} (+{$added}, ↑{$updated})";
                    }
                    $summary = implode(' | ', $messages);
                    return back()->with('success', "동기화 완료 — {$summary} (합계: 추가 {$totalAdded}, 업데이트 {$totalUpdated})");
                }

                // 단일 결과
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
     * 리뷰 소스 등록 (여러개 동시 등록 지원)
     */
    public function storeSource(Request $request)
    {
        $validated = $request->validate([
            'sources' => 'required|array|min:1',
            'sources.*.platform' => 'required|string|max:50',
            'sources.*.product_id' => 'nullable|exists:products,id',
            'sources.*.external_url' => 'nullable|url|max:500',
            'sources.*.external_id' => 'nullable|string|max:100',
        ]);

        $count = 0;
        foreach ($validated['sources'] as $sourceData) {
            $sourceData['platform_name'] = ProductReviewSource::$platforms[$sourceData['platform']] ?? $sourceData['platform'];
            $sourceData['is_active'] = true;
            if (empty($sourceData['product_id'])) {
                $sourceData['product_id'] = null;
            }
            ProductReviewSource::create($sourceData);
            $count++;
        }

        return back()->with('success', "리뷰 소스 {$count}개가 등록되었습니다.");
    }

    /**
     * 리뷰 소스 활성/비활성 토글
     */
    public function toggleSource(ProductReviewSource $source)
    {
        $source->update(['is_active' => !$source->is_active]);

        $status = $source->is_active ? '활성화' : '비활성화';
        return back()->with('success', "{$source->platform_label} 소스가 {$status}되었습니다.");
    }

    /**
     * 리뷰 소스 삭제
     */
    public function destroySource(ProductReviewSource $source)
    {
        $source->delete();
        return back()->with('success', '리뷰 소스가 삭제되었습니다.');
    }

    /**
     * 미매칭 리뷰 일괄 매칭 (다대다: 1개 리뷰 → 여러 상품 복제)
     */
    public function matchReviews()
    {
        // 리뷰 소스 캐시: platform → external_id(trim) → [product_id, ...] (다대다)
        $sourceMap = [];
        $sources = ProductReviewSource::whereNotNull('external_id')
            ->whereNotNull('product_id')
            ->where('is_active', true)
            ->get();
        foreach ($sources as $src) {
            $key = trim((string) $src->external_id);
            if ($key !== '') {
                $sourceMap[$src->platform][$key][] = $src->product_id;
            }
        }

        // products 테이블 code → id 캐시
        $productMap = Product::whereNotNull('code')
            ->pluck('id', 'code')
            ->toArray();

        // 미매칭 리뷰 (product_id가 NULL)
        $unmatched = ProductReview::whereNull('product_id')
            ->where(function ($q) {
                $q->whereNotNull('platform_product_code')
                  ->orWhereNotNull('review_source_id');
            })
            ->get();

        $matched = 0;

        foreach ($unmatched as $review) {
            $productIds = [];
            $code = trim((string) ($review->platform_product_code ?? ''));
            $sourceId = trim((string) ($review->review_source_id ?? ''));

            // 1단계: platform_product_code로 소스 매칭
            if ($code !== '' && isset($sourceMap[$review->platform][$code])) {
                $productIds = $sourceMap[$review->platform][$code];
            }

            // 1-1단계: review_source_id로도 시도
            if (empty($productIds) && $sourceId !== '' && isset($sourceMap[$review->platform][$sourceId])) {
                $productIds = $sourceMap[$review->platform][$sourceId];
            }

            // 2단계: products 테이블에서 code 매칭
            if (empty($productIds) && $code !== '' && isset($productMap[$code])) {
                $productIds = [$productMap[$code]];
            }

            if (!empty($productIds)) {
                $firstAssigned = false;

                foreach ($productIds as $productId) {
                    // 이미 같은 조합이 있는지 확인
                    $exists = ProductReview::where('platform', $review->platform)
                        ->where('external_id', $review->external_id)
                        ->where('product_id', $productId)
                        ->exists();

                    if ($exists) {
                        // 이미 매칭된 리뷰가 있으면 미매칭 중복은 삭제
                        if (!$firstAssigned) {
                            $review->delete();
                            $firstAssigned = true;
                        }
                        continue;
                    }

                    if (!$firstAssigned) {
                        // 첫 번째 product_id는 기존 리뷰에 할당
                        $review->update(['product_id' => $productId]);
                        $firstAssigned = true;
                    } else {
                        // 나머지 product_id는 리뷰 복제
                        $clone = $review->replicate();
                        $clone->product_id = $productId;
                        $clone->save();
                    }
                    $matched++;
                }
            }
        }

        $total = $unmatched->count();
        $stillUnmatched = ProductReview::whereNull('product_id')->count();

        // 소스맵 키 요약 로그
        $sourceInfo = collect($sourceMap)->map(fn($platforms) => count($platforms))->toArray();
        Log::info('리뷰 매칭 결과', [
            'total_unmatched' => $total,
            'matched' => $matched,
            'still_unmatched' => $stillUnmatched,
            'source_map_keys' => $sourceInfo,
        ]);

        return back()->with('success', "일괄 매칭 완료: 미매칭 {$total}개 → {$matched}건 매칭 (잔여 미매칭: {$stillUnmatched}개)");
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
