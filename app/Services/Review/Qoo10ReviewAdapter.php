<?php

namespace App\Services\Review;

use App\Models\ProductReviewSource;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class Qoo10ReviewAdapter implements ReviewAdapterInterface
{
    protected string $screenshotPath;

    public function __construct()
    {
        $this->screenshotPath = storage_path('app/qoo10_screenshots');

        if (!is_dir($this->screenshotPath)) {
            mkdir($this->screenshotPath, 0755, true);
        }
    }

    /**
     * Qoo10 공개 상품 페이지에서 리뷰 가져오기
     */
    public function fetchReviews(ProductReviewSource $source): array
    {
        $productCode = $source->external_id;

        if (empty($productCode)) {
            Log::warning("Qoo10: Missing product code for source {$source->id}");
            return $this->getDefaultData($source);
        }

        try {
            $html = $this->scrapeProductPage($productCode, $source->id);
            return $this->parseReviewHtml($html);
        } catch (\Exception $e) {
            Log::error("Qoo10 review fetch failed: " . $e->getMessage());
            return $this->getDefaultData($source);
        }
    }

    /**
     * 공개 상품 페이지 스크래핑
     */
    protected function scrapeProductPage(string $productCode, ?int $sourceId = null): string
    {
        $productUrl = "https://www.qoo10.jp/g/{$productCode}";

        $html = Browsershot::url($productUrl)
            ->noSandbox()
            ->dismissDialogs()
            ->timeout(30000)
            ->delay(5000)
            ->setOption('args', ['--lang=ja-JP'])
            ->bodyHtml();

        // 디버깅용 HTML 저장
        if (config('app.debug')) {
            file_put_contents(
                $this->screenshotPath . "/qoo10_{$sourceId}_" . date('Ymd_His') . '.html',
                $html
            );
        }

        return $html;
    }

    /**
     * HTML에서 리뷰 데이터 파싱
     */
    protected function parseReviewHtml(string $html): array
    {
        $reviews = [];
        $reviewCount = 0;
        $averageRating = 0;

        // 총 리뷰 수 파싱
        if (preg_match('/レビュー[^\d]*?(\d+)/u', $html, $m)) {
            $reviewCount = (int) $m[1];
        }

        // 평균 평점 파싱 (review_total_score 클래스)
        if (preg_match('/class="review_total_score"[^>]*>(\d+\.?\d*)/u', $html, $m)) {
            $averageRating = (float) $m[1];
        }

        // DOM 파싱
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new \DOMXPath($dom);

        // review_txt 클래스에서 리뷰 텍스트 추출
        $reviewTexts = $xpath->query("//p[contains(@class, 'review_txt')]");

        foreach ($reviewTexts as $i => $node) {
            $content = trim($node->textContent);

            // "フォトレビューだけ" 같은 UI 텍스트 제외
            if (empty($content) || mb_strlen($content) < 20) {
                continue;
            }

            // 별점 찾기 (리뷰 텍스트 주변에서)
            $rating = $this->findRatingNearNode($xpath, $node);

            // 작성자 찾기
            $author = $this->findAuthorNearNode($xpath, $node);

            // 날짜 찾기
            $date = $this->findDateNearNode($xpath, $node);

            // 이미지 찾기
            $images = $this->findImagesNearNode($xpath, $node);

            $reviews[] = [
                'external_id' => 'qoo10_' . md5($content),
                'rating' => $rating,
                'title' => null,
                'content' => $content,
                'author' => $author,
                'purchased_option' => null,
                'images' => $images,
                'reviewed_at' => $date,
            ];
        }

        libxml_clear_errors();

        // 리뷰 수가 파싱되지 않았으면 추출된 리뷰 개수 사용
        if ($reviewCount === 0) {
            $reviewCount = count($reviews);
        }

        // 평균 평점이 파싱되지 않았으면 계산
        if ($averageRating === 0 && !empty($reviews)) {
            $totalRating = array_sum(array_column($reviews, 'rating'));
            $averageRating = round($totalRating / count($reviews), 1);
        }

        return [
            'review_count' => $reviewCount,
            'average_rating' => $averageRating,
            'recent_reviews' => array_slice($reviews, 0, 10),
            'reviews' => $reviews,
        ];
    }

    /**
     * 리뷰 노드 주변에서 별점 찾기
     */
    protected function findRatingNearNode(\DOMXPath $xpath, \DOMNode $node): float
    {
        // 부모 요소에서 별점 찾기
        $parent = $node->parentNode;
        for ($i = 0; $i < 5 && $parent; $i++) {
            $ratingNodes = $xpath->query(".//*[contains(@class, 'review_rating_star')]/@style", $parent);
            foreach ($ratingNodes as $ratingNode) {
                if (preg_match('/width:\s*(\d+)%/', $ratingNode->nodeValue, $m)) {
                    return round((int) $m[1] / 20, 1); // 100% = 5점
                }
            }

            // 점수 텍스트 찾기
            $scoreNodes = $xpath->query(".//*[contains(@class, 'score') or contains(@class, 'star')]", $parent);
            foreach ($scoreNodes as $scoreNode) {
                if (preg_match('/(\d+\.?\d*)/', $scoreNode->textContent, $m)) {
                    $score = (float) $m[1];
                    if ($score <= 5) {
                        return $score;
                    }
                }
            }

            $parent = $parent->parentNode;
        }

        return 5.0; // 기본값
    }

    /**
     * 리뷰 노드 주변에서 작성자 찾기
     */
    protected function findAuthorNearNode(\DOMXPath $xpath, \DOMNode $node): ?string
    {
        $parent = $node->parentNode;
        for ($i = 0; $i < 5 && $parent; $i++) {
            $authorNodes = $xpath->query(".//*[contains(@class, 'user') or contains(@class, 'name') or contains(@class, 'nick')]", $parent);
            foreach ($authorNodes as $authorNode) {
                $name = trim($authorNode->textContent);
                if (!empty($name) && mb_strlen($name) < 30) {
                    return $this->maskName($name);
                }
            }
            $parent = $parent->parentNode;
        }

        return null;
    }

    /**
     * 리뷰 노드 주변에서 날짜 찾기
     */
    protected function findDateNearNode(\DOMXPath $xpath, \DOMNode $node): ?string
    {
        $parent = $node->parentNode;
        for ($i = 0; $i < 5 && $parent; $i++) {
            $dateNodes = $xpath->query(".//*[contains(@class, 'date') or contains(@class, 'time')]", $parent);
            foreach ($dateNodes as $dateNode) {
                $text = trim($dateNode->textContent);
                if (preg_match('/\d{4}[-\/\.]\d{1,2}[-\/\.]\d{1,2}/', $text, $m)) {
                    return $m[0];
                }
            }
            $parent = $parent->parentNode;
        }

        return null;
    }

    /**
     * 리뷰 노드 주변에서 이미지 찾기
     */
    protected function findImagesNearNode(\DOMXPath $xpath, \DOMNode $node): array
    {
        $images = [];
        $parent = $node->parentNode;

        for ($i = 0; $i < 3 && $parent; $i++) {
            $imgNodes = $xpath->query(".//img[contains(@class, 'review') or contains(@class, 'photo')]/@src", $parent);
            foreach ($imgNodes as $img) {
                $src = $img->nodeValue;
                if ($src && !str_contains($src, 'icon') && !str_contains($src, 'star')) {
                    $images[] = $src;
                }
            }

            // review_photo 클래스 안의 이미지
            $photoNodes = $xpath->query(".//*[contains(@class, 'review_photo')]//img/@src", $parent);
            foreach ($photoNodes as $img) {
                $src = $img->nodeValue;
                if ($src && !in_array($src, $images)) {
                    $images[] = $src;
                }
            }

            $parent = $parent->parentNode;
        }

        return array_slice($images, 0, 5);
    }

    /**
     * 이름 마스킹
     */
    protected function maskName(string $name): string
    {
        $name = trim($name);
        if (empty($name)) {
            return '익명';
        }

        $length = mb_strlen($name);
        if ($length <= 2) {
            return mb_substr($name, 0, 1) . '*';
        }

        return mb_substr($name, 0, 1) . str_repeat('*', $length - 2) . mb_substr($name, -1);
    }

    /**
     * 기본 데이터 반환
     */
    protected function getDefaultData(ProductReviewSource $source): array
    {
        return [
            'review_count' => $source->review_count ?? 0,
            'average_rating' => $source->average_rating ?? 0,
            'recent_reviews' => $source->recent_reviews ?? [],
            'reviews' => [],
        ];
    }
}
