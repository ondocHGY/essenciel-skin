<?php

namespace App\Services\Review;

use App\Models\ProductReviewSource;

interface ReviewAdapterInterface
{
    /**
     * 리뷰 데이터 가져오기
     *
     * @return array{
     *     review_count: int,
     *     average_rating: float,
     *     recent_reviews: array,
     *     reviews: array<array{
     *         external_id: ?string,
     *         rating: float,
     *         title: ?string,
     *         content: string,
     *         author: ?string,
     *         purchased_option: ?string,
     *         images: ?array,
     *         reviewed_at: ?string
     *     }>
     * }
     */
    public function fetchReviews(ProductReviewSource $source): array;
}
