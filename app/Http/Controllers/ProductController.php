<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
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

        // 다른 제품 분석용 제품 목록
        $otherProducts = Product::where('id', '!=', $product->id)
            ->orderBy('name')
            ->get();

        return view('product.show', compact('product', 'platformReviewCounts', 'otherProducts', 'ratingDistribution', 'averageRating'));
    }
}
