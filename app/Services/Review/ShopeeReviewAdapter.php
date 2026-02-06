<?php

namespace App\Services\Review;

use App\Models\ProductReviewSource;
use Illuminate\Support\Facades\Http;

class ShopeeReviewAdapter implements ReviewAdapterInterface
{
    /**
     * Shopee 리뷰 가져오기
     * Judge.me API 사용 예시 (다른 리뷰 앱 사용 시 수정 필요)
     */
    public function fetchReviews(ProductReviewSource $source): array
    {
        $config = $source->api_config ?? [];

        // API 설정이 없으면 기본값 반환
        if (empty($config['api_token']) || empty($config['shop_domain'])) {
            return $this->getDefaultData($source);
        }

        try {
            // Judge.me API 예시
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_token'],
            ])->get("https://judge.me/api/v1/reviews", [
                'shop_domain' => $config['shop_domain'],
                'product_id' => $source->external_id,
                'per_page' => 10,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'review_count' => $data['total_count'] ?? 0,
                    'average_rating' => $data['average_rating'] ?? 0,
                    'recent_reviews' => $this->formatReviews($data['reviews'] ?? []),
                ];
            }
        } catch (\Exception $e) {
            // API 실패 시 기존 데이터 유지
        }

        return $this->getDefaultData($source);
    }

    protected function formatReviews(array $reviews): array
    {
        return collect($reviews)->take(10)->map(fn($review) => [
            'id' => $review['id'] ?? null,
            'rating' => $review['rating'] ?? 5,
            'title' => $review['title'] ?? '',
            'content' => $review['body'] ?? '',
            'author' => $review['reviewer']['name'] ?? '익명',
            'created_at' => $review['created_at'] ?? null,
        ])->toArray();
    }

    protected function getDefaultData(ProductReviewSource $source): array
    {
        return [
            'review_count' => $source->review_count,
            'average_rating' => $source->average_rating,
            'recent_reviews' => $source->recent_reviews ?? [],
        ];
    }
}
