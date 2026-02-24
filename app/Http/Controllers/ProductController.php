<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Request $request, string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();

        // 세션 ID 생성 (없으면)
        if (!$request->session()->has('skincare_session_id')) {
            $request->session()->put('skincare_session_id', Str::uuid()->toString());
        }

        // 플랫폼별 실제 리뷰 수 조회
        $platformReviewCounts = ProductReview::where('product_id', $product->id)
            ->selectRaw('platform, count(*) as count')
            ->groupBy('platform')
            ->pluck('count', 'platform')
            ->toArray();

        // 평점 분포 조회 (1~5점별 개수)
        $ratingDistribution = ProductReview::where('product_id', $product->id)
            ->whereNotNull('rating')
            ->where('rating', '>', 0)
            ->selectRaw('FLOOR(rating) as star, count(*) as count')
            ->groupBy('star')
            ->pluck('count', 'star')
            ->toArray();

        // 평균 평점
        $averageRating = ProductReview::where('product_id', $product->id)
            ->whereNotNull('rating')
            ->where('rating', '>', 0)
            ->avg('rating');

        // 플랫폼별 최근 동기화 날짜
        $platformSyncDates = ProductReviewSource::where('product_id', $product->id)
            ->whereNotNull('synced_at')
            ->selectRaw('platform, MAX(synced_at) as last_synced')
            ->groupBy('platform')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->platform => \Carbon\Carbon::parse($row->last_synced)->format('Y.m.d')])
            ->toArray();

        // 플랫폼별 최근 리뷰 3개
        $platformSampleReviews = [];
        $platformsWithReviews = ProductReview::where('product_id', $product->id)
            ->select('platform')
            ->distinct()
            ->pluck('platform');

        foreach ($platformsWithReviews as $platform) {
            $platformSampleReviews[$platform] = ProductReview::where('product_id', $product->id)
                ->where('platform', $platform)
                ->orderByDesc('reviewed_at')
                ->limit(3)
                ->get()
                ->map(fn ($review) => [
                    'author' => $review->masked_author,
                    'rating' => $review->rating,
                    'date' => $review->reviewed_at?->format('Y.m.d'),
                    'summary' => $review->getSummary(80),
                ]);
        }

        // 다른 제품 분석용 제품 목록
        $otherProducts = Product::where('id', '!=', $product->id)
            ->where('code', 'not like', '%TEST%')
            ->orderBy('name')
            ->get();

        return view('product.show', compact('product', 'platformReviewCounts', 'otherProducts', 'ratingDistribution', 'averageRating', 'platformSyncDates', 'platformSampleReviews'));
    }
}
