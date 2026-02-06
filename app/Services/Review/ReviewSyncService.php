<?php

namespace App\Services\Review;

use App\Models\ProductReview;
use App\Models\ProductReviewSource;
use Illuminate\Support\Facades\Log;

class ReviewSyncService
{
    protected array $adapters = [];

    public function __construct()
    {
        $this->adapters = [
            ProductReviewSource::PLATFORM_SHOPEE => new ShopeeReviewAdapter(),
            ProductReviewSource::PLATFORM_NAVER => new NaverReviewAdapter(),
            ProductReviewSource::PLATFORM_QOO10 => new Qoo10ReviewAdapter(),
        ];
    }

    /**
     * 모든 활성 소스 동기화
     */
    public function syncAll(int $hoursThreshold = 3): array
    {
        $sources = ProductReviewSource::active()
            ->needsSync($hoursThreshold)
            ->get();

        $results = [
            'total' => $sources->count(),
            'success' => 0,
            'failed' => 0,
            'reviews_added' => 0,
            'errors' => [],
        ];

        foreach ($sources as $source) {
            try {
                $syncResult = $this->syncSource($source);
                $results['success']++;
                $results['reviews_added'] += $syncResult['reviews_added'] ?? 0;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'source_id' => $source->id,
                    'platform' => $source->platform,
                    'error' => $e->getMessage(),
                ];
                Log::error("Review sync failed for source {$source->id}: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * 특정 소스 동기화
     */
    public function syncSource(ProductReviewSource $source): array
    {
        $adapter = $this->adapters[$source->platform] ?? null;

        if (!$adapter) {
            throw new \Exception("No adapter found for platform: {$source->platform}");
        }

        $data = $adapter->fetchReviews($source);

        // 소스 정보 업데이트
        $source->update([
            'review_count' => $data['review_count'],
            'average_rating' => $data['average_rating'],
            'recent_reviews' => $data['recent_reviews'] ?? [],
            'synced_at' => now(),
        ]);

        // 개별 리뷰 저장
        $reviewsAdded = 0;
        if (!empty($data['reviews'])) {
            $reviewsAdded = $this->saveReviews($source, $data['reviews']);
        }

        return [
            'success' => true,
            'reviews_added' => $reviewsAdded,
        ];
    }

    /**
     * 리뷰 데이터를 DB에 저장
     */
    protected function saveReviews(ProductReviewSource $source, array $reviews): int
    {
        $added = 0;

        foreach ($reviews as $reviewData) {
            // external_id가 있으면 중복 체크
            if (!empty($reviewData['external_id'])) {
                $exists = ProductReview::where('platform', $source->platform)
                    ->where('external_id', $reviewData['external_id'])
                    ->exists();

                if ($exists) {
                    continue;
                }
            }

            ProductReview::create([
                'product_id' => $source->product_id,
                'review_source_id' => $source->id,
                'platform' => $source->platform,
                'external_id' => $reviewData['external_id'] ?? null,
                'rating' => $reviewData['rating'] ?? 5.0,
                'title' => $reviewData['title'] ?? null,
                'content' => $reviewData['content'] ?? '',
                'author' => $reviewData['author'] ?? null,
                'purchased_option' => $reviewData['purchased_option'] ?? null,
                'images' => $reviewData['images'] ?? null,
                'is_featured' => false,
                'is_visible' => true,
                'reviewed_at' => $this->parseDate($reviewData['reviewed_at'] ?? null),
            ]);

            $added++;
        }

        return $added;
    }

    /**
     * 날짜 문자열 파싱
     */
    protected function parseDate(?string $dateString): ?\DateTime
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return new \DateTime($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 특정 제품의 모든 소스 동기화
     */
    public function syncProduct(int $productId): array
    {
        $sources = ProductReviewSource::where('product_id', $productId)
            ->active()
            ->get();

        $results = [
            'platforms' => [],
            'total_reviews_added' => 0,
        ];

        foreach ($sources as $source) {
            try {
                $syncResult = $this->syncSource($source);
                $results['platforms'][$source->platform] = [
                    'success' => true,
                    'reviews_added' => $syncResult['reviews_added'],
                ];
                $results['total_reviews_added'] += $syncResult['reviews_added'];
            } catch (\Exception $e) {
                $results['platforms'][$source->platform] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
