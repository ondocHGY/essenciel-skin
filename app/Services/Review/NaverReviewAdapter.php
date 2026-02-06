<?php

namespace App\Services\Review;

use App\Models\ProductReviewSource;
use Illuminate\Support\Facades\Http;

class NaverReviewAdapter implements ReviewAdapterInterface
{
    /**
     * 네이버 스마트스토어 리뷰 가져오기
     * 네이버 커머스 API 사용
     */
    public function fetchReviews(ProductReviewSource $source): array
    {
        $config = $source->api_config ?? [];

        // API 설정이 없으면 기본값 반환
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            return $this->getDefaultData($source);
        }

        try {
            // 1. 액세스 토큰 발급
            $tokenResponse = Http::asForm()->post('https://api.commerce.naver.com/external/v1/oauth2/token', [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'grant_type' => 'client_credentials',
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get access token');
            }

            $accessToken = $tokenResponse->json('access_token');

            // 2. 상품 리뷰 조회
            $reviewResponse = Http::withToken($accessToken)
                ->get("https://api.commerce.naver.com/external/v1/products/{$source->external_id}/reviews", [
                    'page' => 1,
                    'size' => 10,
                ]);

            if ($reviewResponse->successful()) {
                $data = $reviewResponse->json();

                return [
                    'review_count' => $data['totalElements'] ?? 0,
                    'average_rating' => $this->calculateAverageRating($data['content'] ?? []),
                    'recent_reviews' => $this->formatReviews($data['content'] ?? []),
                ];
            }
        } catch (\Exception $e) {
            // API 실패 시 기존 데이터 유지
        }

        return $this->getDefaultData($source);
    }

    protected function calculateAverageRating(array $reviews): float
    {
        if (empty($reviews)) {
            return 0;
        }

        $total = array_sum(array_column($reviews, 'score'));
        return round($total / count($reviews), 1);
    }

    protected function formatReviews(array $reviews): array
    {
        return collect($reviews)->take(10)->map(fn($review) => [
            'id' => $review['id'] ?? null,
            'rating' => $review['score'] ?? 5,
            'title' => '',
            'content' => $review['content'] ?? '',
            'author' => $this->maskName($review['writerNickname'] ?? '익명'),
            'created_at' => $review['createDate'] ?? null,
        ])->toArray();
    }

    protected function maskName(string $name): string
    {
        if (mb_strlen($name) <= 2) {
            return $name[0] . '*';
        }
        return mb_substr($name, 0, 1) . str_repeat('*', mb_strlen($name) - 2) . mb_substr($name, -1);
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
