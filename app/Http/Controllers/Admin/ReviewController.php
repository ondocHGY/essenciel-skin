<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\ProductReviewSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ReviewController extends Controller
{
    /**
     * 리뷰 목록
     */
    public function index(Request $request)
    {
        $query = ProductReview::with(['product'])
            ->orderByDesc('reviewed_at');

        // 필터
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }

        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }

        $reviews = $query->paginate(30)->withQueryString();

        $products = Product::orderBy('name')->get();
        $platforms = ProductReviewSource::$platforms;

        // 통계
        $stats = [
            'total' => ProductReview::count(),
            'visible' => ProductReview::visible()->count(),
            'featured' => ProductReview::featured()->count(),
            'average_rating' => round(ProductReview::avg('rating') ?? 0, 2),
        ];

        return view('admin.reviews.index', compact('reviews', 'products', 'platforms', 'stats'));
    }

    /**
     * 리뷰 상세/수정 폼
     */
    public function edit(ProductReview $review)
    {
        $products = Product::orderBy('name')->get();
        $platforms = ProductReviewSource::$platforms;

        return view('admin.reviews.edit', compact('review', 'products', 'platforms'));
    }

    /**
     * 리뷰 업데이트
     */
    public function update(Request $request, ProductReview $review)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'platform' => 'required|string|max:50',
            'rating' => 'required|numeric|min:1|max:5',
            'content' => 'required|string',
            'author' => 'nullable|string|max:100',
            'is_featured' => 'boolean',
            'is_visible' => 'boolean',
        ]);

        $review->update([
            'product_id' => $validated['product_id'],
            'platform' => $validated['platform'],
            'rating' => $validated['rating'],
            'content' => $validated['content'],
            'author' => $validated['author'],
            'is_featured' => $request->boolean('is_featured'),
            'is_visible' => $request->boolean('is_visible'),
        ]);

        return redirect()->route('admin.reviews.index')
            ->with('success', '리뷰가 수정되었습니다.');
    }

    /**
     * 리뷰 삭제
     */
    public function destroy(ProductReview $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')
            ->with('success', '리뷰가 삭제되었습니다.');
    }

    /**
     * 리뷰 표시/숨김 토글
     */
    public function toggleVisibility(ProductReview $review)
    {
        $review->update(['is_visible' => !$review->is_visible]);

        return back()->with('success', '리뷰 표시 상태가 변경되었습니다.');
    }

    /**
     * 리뷰 엑셀 다운로드 (현재 필터 적용)
     */
    public function export(Request $request)
    {
        $query = ProductReview::with(['product'])
            ->orderByDesc('reviewed_at');

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('rating')) {
            $query->where('rating', '>=', $request->rating);
        }

        $reviews = $query->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 헤더
        $headers = ['ID', '제품명', '플랫폼', '평점', '작성자', '리뷰 내용', '작성일', '노출', '추천'];
        $sheet->fromArray($headers, null, 'A1');

        // 헤더 스타일
        $headerStyle = $sheet->getStyle('A1:I1');
        $headerStyle->getFont()->setBold(true);
        $headerStyle->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
        $headerStyle->getFill()->getStartColor()->setRGB('E2E8F0');

        // 데이터
        $platforms = ProductReviewSource::$platforms;
        $row = 2;
        foreach ($reviews as $review) {
            $sheet->setCellValue("A{$row}", $review->id);
            $sheet->setCellValue("B{$row}", $review->product?->name ?? '-');
            $sheet->setCellValue("C{$row}", $platforms[$review->platform] ?? $review->platform);
            $sheet->setCellValue("D{$row}", $review->rating);
            $sheet->setCellValue("E{$row}", $review->author ?? '-');
            $sheet->setCellValue("F{$row}", $review->content);
            $sheet->setCellValue("G{$row}", $review->reviewed_at?->format('Y-m-d') ?? '-');
            $sheet->setCellValue("H{$row}", $review->is_visible ? 'O' : 'X');
            $sheet->setCellValue("I{$row}", $review->is_featured ? 'O' : 'X');
            $row++;
        }

        // 컬럼 너비
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(12);
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(60);
        $sheet->getColumnDimension('G')->setWidth(12);
        $sheet->getColumnDimension('H')->setWidth(8);
        $sheet->getColumnDimension('I')->setWidth(8);

        $filename = '리뷰_' . now()->format('Ymd_His') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * 엑셀 업로드 폼
     */
    public function uploadForm()
    {
        $products = Product::orderBy('name')->get();
        $platforms = ProductReviewSource::$platforms;

        return view('admin.reviews.upload', compact('products', 'platforms'));
    }

    /**
     * 엑셀 업로드 처리
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'product_id' => 'nullable|exists:products,id',
            'platform' => 'required|string|max:50',
        ]);

        $file = $request->file('file');
        $defaultProductId = $request->product_id;
        $platform = $request->platform;

        // 화해는 전용 처리
        if ($platform === 'hwahae') {
            return $this->uploadHwahae($file, $defaultProductId);
        }

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // W컨셉: 1행은 메타데이터, 2행이 헤더
            if ($platform === 'wconcept') {
                array_shift($rows); // 1행(메타데이터) 제거
            }

            // 첫 번째 행은 헤더
            $headers = array_map('trim', array_map('strval', $rows[0] ?? []));
            $headerMap = $this->mapHeaders($headers, $platform);

            if (!isset($headerMap['content'])) {
                return back()->withErrors(['file' => '리뷰 내용 컬럼을 찾을 수 없습니다. (댓글, 내용, content, review 등)']);
            }

            $added = 0;
            $updated = 0;
            $skipped = 0;

            // 상품코드 → product_id 캐시
            $productCache = [];

            // 리뷰 소스 캐시: external_id → [product_id, ...] (다대다)
            $sourceCache = [];
            $sources = ProductReviewSource::where('platform', $platform)
                ->where('is_active', true)
                ->whereNotNull('external_id')
                ->whereNotNull('product_id')
                ->get();
            foreach ($sources as $src) {
                $sourceCache[$src->external_id][] = $src->product_id;
            }

            DB::beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // 빈 행 스킵
                if (empty(array_filter($row))) {
                    continue;
                }

                $content = trim($row[$headerMap['content']] ?? '');

                // 내용이 없거나 너무 짧으면 스킵
                if (empty($content) || mb_strlen($content) < 5) {
                    $skipped++;
                    continue;
                }

                // 상품코드로 product_id 자동 매칭 (다대다)
                $productCode = $this->extractValue($row, $headerMap, 'product_code');
                $productIds = [];

                if ($productCode) {
                    // 1단계: 리뷰 소스에서 매칭 (여러 product_id 가능)
                    if (isset($sourceCache[$productCode])) {
                        $productIds = $sourceCache[$productCode];
                    } else {
                        // 2단계: products 테이블에서 code 매칭
                        if (!isset($productCache[$productCode])) {
                            $productCache[$productCode] = Product::where('code', $productCode)->value('id');
                        }
                        if ($productCache[$productCode]) {
                            $productIds = [$productCache[$productCode]];
                        }
                    }
                }

                // 3단계: 상품명 키워드로 매칭
                if (empty($productIds)) {
                    $productName = $this->extractValue($row, $headerMap, 'product_name');
                    $keywordMatch = $this->matchProductByKeyword($productName);
                    if ($keywordMatch) {
                        $productIds = [$keywordMatch];
                    }
                }

                // 폼에서 선택한 기본값 (매칭 결과가 없을 때)
                if (empty($productIds)) {
                    $productIds = [$defaultProductId]; // null일 수 있음
                }

                // 공통 리뷰 데이터
                $baseReviewData = [
                    'platform' => $platform,
                    'platform_product_code' => $productCode,
                    'product_name' => $this->extractValue($row, $headerMap, 'product_name'),
                    'content' => $content,
                    'rating' => $this->extractRating($row, $headerMap),
                    'author' => $this->extractValue($row, $headerMap, 'author'),
                    'reviewed_at' => $this->extractDate($row, $headerMap),
                    'is_visible' => true,
                ];

                // external_id 생성 (중복 체크용)
                $externalId = isset($headerMap['external_id']) && !empty($row[$headerMap['external_id']])
                    ? "{$platform}_{$row[$headerMap['external_id']]}"
                    : "{$platform}_" . md5($content);

                $baseReviewData['external_id'] = $externalId;

                // 각 product_id별로 리뷰 저장/업데이트
                foreach ($productIds as $productId) {
                    $reviewData = array_merge($baseReviewData, ['product_id' => $productId]);

                    // 기존 리뷰 확인 (platform + external_id + product_id 조합)
                    $query = ProductReview::where('platform', $platform)
                        ->where('external_id', $externalId);

                    if ($productId !== null) {
                        $query->where('product_id', $productId);
                    } else {
                        $query->whereNull('product_id');
                    }

                    $existing = $query->first();

                    if ($existing) {
                        $existing->update($reviewData);
                        $updated++;
                    } else {
                        ProductReview::create($reviewData);
                        $added++;
                    }
                }
            }

            DB::commit();

            return redirect()->route('admin.reviews.index')
                ->with('success', "업로드 완료: {$added}개 추가, {$updated}개 업데이트, {$skipped}개 스킵");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('리뷰 업로드 실패: ' . $e->getMessage());

            return back()->withErrors(['file' => '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }
    }

    /**
     * 샘플 엑셀 다운로드
     */
    public function downloadSample()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // 헤더
        $headers = ['리뷰ID', '평점', '내용', '작성자', '작성일'];
        $sheet->fromArray($headers, null, 'A1');

        // 샘플 데이터
        $sampleData = [
            ['12345', '5', '정말 좋은 제품이에요! 피부가 촉촉해졌습니다.', '김**', '2026-01-15'],
            ['12346', '4', '향이 좋고 발림성이 좋아요.', '이**', '2026-01-14'],
            ['12347', '5', '재구매 의사 있습니다.', '박**', '2026-01-13'],
        ];
        $sheet->fromArray($sampleData, null, 'A2');

        // 컬럼 너비 조정
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(50);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);

        // 다운로드
        $filename = 'review_upload_sample.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }

    /**
     * 플랫폼별 컬럼 매핑 프리셋
     */
    private function getPlatformColumnPresets(): array
    {
        return [
            // 네이버 스마트스토어
            'naver' => [
                'content' => '리뷰상세내용',
                'rating' => '구매자평점',
                'author' => '등록자',
                'reviewed_at' => '리뷰등록일',
                'external_id' => '리뷰글번호',
                'product_code' => '상품번호',
                'product_name' => '상품명',
            ],
            // Qoo10 QSM
            'qoo10' => [
                'content' => '댓글',
                'rating' => '만족도',
                'author' => '작성자ID',
                'reviewed_at' => '작성일',
                'external_id' => '상품평번호_h',
                'product_code' => '상품코드',
                'product_name' => '상품명',
            ],
            // 쿠팡
            'coupang' => [
                'content' => '상품평내용',
                'rating' => '별점',
                'author' => '작성자',
                'reviewed_at' => '작성일',
                'external_id' => '상품평번호',
                'product_code' => '상품번호',
                'product_name' => '상품명',
            ],
            // Shopee
            'shopee' => [
                'content' => 'Review Comment',
                'rating' => 'Rating Star',
                'author' => 'Username',
                'reviewed_at' => 'Review Time',
                'external_id' => 'Review ID',
                'product_code' => 'Product ID',
                'product_name' => 'Product Name',
            ],
            // 아마존
            'amazon' => [
                'content' => 'Body',
                'rating' => 'Star Rating',
                'author' => 'Author',
                'reviewed_at' => 'Date',
                'external_id' => 'Review ID',
                'product_code' => 'ASIN',
                'product_name' => 'Product Title',
            ],
            // W컨셉
            'wconcept' => [
                'content' => '제목',
                'rating' => '평점',
                'author' => '작성자',
                'reviewed_at' => '작성일',
                'external_id' => '주문번호',
                'product_name' => '상품명',
            ],
        ];
    }

    /**
     * 상품명 키워드 → product_id 매핑
     */
    private const PRODUCT_KEYWORD_MAP = [
        '브라이트' => 9,
        '하이드라' => 10,
        '부스팅' => 11,
        '수더' => 12,
    ];

    /**
     * 상품명에서 키워드로 product_id 매칭
     */
    private function matchProductByKeyword(?string $productName): ?int
    {
        if (empty($productName)) {
            return null;
        }
        foreach (self::PRODUCT_KEYWORD_MAP as $keyword => $productId) {
            if (str_contains($productName, $keyword)) {
                return $productId;
            }
        }
        return null;
    }

    /**
     * 화해 전용 업로드 처리
     * - 3번째 시트(리뷰RAW데이터) 사용
     * - 좋은점+아쉬운점+꿀팁 합쳐서 content 생성
     * - 상품명 키워드로 product_id 매칭
     */
    private function uploadHwahae($file, $defaultProductId)
    {
        try {
            $spreadsheet = IOFactory::load($file->getPathname());

            // 3번째 시트 (index 2)
            if ($spreadsheet->getSheetCount() < 3) {
                return back()->withErrors(['file' => '화해 엑셀 파일에 3번째 시트(리뷰RAW데이터)가 없습니다.']);
            }
            $worksheet = $spreadsheet->getSheet(2);
            $rows = $worksheet->toArray();

            $headers = array_map('trim', array_map('strval', $rows[0] ?? []));
            $headersLower = array_map(fn($h) => mb_strtolower($h), $headers);

            // 컬럼 인덱스 찾기
            $colMap = [];
            $targetCols = ['닉네임', '제품명', '별점', '좋은점', '아쉬운점', '꿀팁'];
            foreach ($targetCols as $col) {
                $idx = array_search(mb_strtolower($col), $headersLower);
                if ($idx !== false) {
                    $colMap[$col] = $idx;
                }
            }

            if (!isset($colMap['좋은점'])) {
                return back()->withErrors(['file' => '화해 엑셀에서 "좋은점" 컬럼을 찾을 수 없습니다.']);
            }

            $added = 0;
            $updated = 0;
            $skipped = 0;

            DB::beginTransaction();

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                if (empty(array_filter($row))) {
                    continue;
                }

                // 좋은점 + 아쉬운점 + 꿀팁 합치기
                $parts = [];
                foreach (['좋은점', '아쉬운점', '꿀팁'] as $col) {
                    if (isset($colMap[$col])) {
                        $val = trim($row[$colMap[$col]] ?? '');
                        if (!empty($val)) {
                            $parts[] = $val;
                        }
                    }
                }
                $content = implode("\n", $parts);

                if (empty($content) || mb_strlen($content) < 5) {
                    $skipped++;
                    continue;
                }

                $author = isset($colMap['닉네임']) ? trim($row[$colMap['닉네임']] ?? '') : '';
                $productName = isset($colMap['제품명']) ? trim($row[$colMap['제품명']] ?? '') : '';

                $rating = 5.0;
                if (isset($colMap['별점']) && is_numeric($row[$colMap['별점']] ?? '')) {
                    $rating = max(1, min(5, (float)$row[$colMap['별점']]));
                }

                // 상품명 키워드로 product_id 매칭
                $productId = $this->matchProductByKeyword($productName) ?? $defaultProductId;

                // external_id 생성
                $externalId = 'hwahae_' . substr(md5($author . $productName . mb_substr($content, 0, 50)), 0, 12);

                $reviewData = [
                    'platform' => 'hwahae',
                    'platform_product_code' => null,
                    'product_name' => $productName ?: null,
                    'content' => $content,
                    'rating' => $rating,
                    'author' => !empty($author) ? $author : null,
                    'reviewed_at' => null,
                    'is_visible' => true,
                    'external_id' => $externalId,
                    'product_id' => $productId,
                ];

                $existing = ProductReview::where('platform', 'hwahae')
                    ->where('external_id', $externalId)
                    ->first();

                if ($existing) {
                    $existing->update($reviewData);
                    $updated++;
                } else {
                    ProductReview::create($reviewData);
                    $added++;
                }
            }

            DB::commit();

            return redirect()->route('admin.reviews.index')
                ->with('success', "화해 업로드 완료: {$added}개 추가, {$updated}개 업데이트, {$skipped}개 스킵");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('화해 리뷰 업로드 실패: ' . $e->getMessage());

            return back()->withErrors(['file' => '파일 처리 중 오류가 발생했습니다: ' . $e->getMessage()]);
        }
    }

    /**
     * 헤더 매핑
     */
    private function mapHeaders(array $headers, string $platform): array
    {
        $map = [];
        $headersLower = array_map(fn ($h) => mb_strtolower(trim((string) $h)), $headers);

        // 1차: 플랫폼별 프리셋으로 exact match
        $presets = $this->getPlatformColumnPresets();
        if (isset($presets[$platform])) {
            foreach ($presets[$platform] as $field => $columnName) {
                $idx = array_search(mb_strtolower($columnName), $headersLower);
                if ($idx !== false) {
                    $map[$field] = $idx;
                }
            }
        }

        // 2차: 프리셋으로 못 찾은 필드는 범용 exact match
        $genericExact = [
            'content' => ['댓글', '내용', 'content', 'review', 'レビュー内容', '리뷰내용', '리뷰상세내용', '상품평내용'],
            'rating' => ['평점', '만족도', 'rating', 'score', '점수', '評価', '별점', '구매자평점'],
            'author' => ['작성자', '작성자ID', 'author', '購入者', '닉네임', '등록자', 'username'],
            'reviewed_at' => ['작성일', '등록일', 'date', '날짜', '日付', '登録日', '리뷰등록일'],
            'external_id' => ['리뷰ID', '상품평번호', 'review_id', '상품평번호_h', '리뷰글번호', '상품평번호'],
            'product_code' => ['상품코드', '상품번호', '商品コード', '商品番号', 'GdNo', 'Product Code', 'Item Code', 'ASIN'],
            'product_name' => ['상품명', '商品名', 'Product Name', 'Product Title'],
        ];

        foreach ($headersLower as $index => $headerLower) {
            if (in_array($index, $map)) continue;

            foreach ($genericExact as $field => $keywords) {
                if (isset($map[$field])) continue;

                foreach ($keywords as $keyword) {
                    if (mb_strtolower($keyword) === $headerLower) {
                        $map[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        // 3차: 아직 못 찾은 필드는 substring match
        $substringKeywords = [
            'content' => ['내용', 'content', 'review', 'body'],
            'rating' => ['평점', 'rating', 'star'],
            'author' => ['작성자', 'author'],
            'reviewed_at' => ['작성일', 'date'],
            'product_code' => ['상품코드', 'product code'],
            'product_name' => ['상품명', 'product name'],
        ];

        foreach ($headersLower as $index => $headerLower) {
            if (in_array($index, $map)) continue;

            foreach ($substringKeywords as $field => $keywords) {
                if (isset($map[$field])) continue;

                foreach ($keywords as $keyword) {
                    if (str_contains($headerLower, mb_strtolower($keyword))) {
                        $map[$field] = $index;
                        break 2;
                    }
                }
            }
        }

        return $map;
    }

    /**
     * 평점 추출
     */
    private function extractRating(array $row, array $headerMap): float
    {
        if (!isset($headerMap['rating'])) {
            return 5.0;
        }

        $value = $row[$headerMap['rating']] ?? '';

        if (is_numeric($value)) {
            $rating = (float) $value;
            return max(1, min(5, $rating));
        }

        return 5.0;
    }

    /**
     * 값 추출
     */
    private function extractValue(array $row, array $headerMap, string $field): ?string
    {
        if (!isset($headerMap[$field])) {
            return null;
        }

        $value = trim($row[$headerMap[$field]] ?? '');

        return !empty($value) ? $value : null;
    }

    /**
     * 날짜 추출
     */
    private function extractDate(array $row, array $headerMap): ?\DateTime
    {
        if (!isset($headerMap['reviewed_at'])) {
            return null;
        }

        $value = trim($row[$headerMap['reviewed_at']] ?? '');

        if (empty($value)) {
            return null;
        }

        // 날짜 형식 파싱 시도
        $formats = ['Y-m-d', 'Y/m/d', 'Y.m.d', 'Y-m-d H:i:s', 'Y/m/d H:i:s', 'Y.m.d. H:i:s'];

        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date;
            }
        }

        // Excel 날짜 숫자 형식
        if (is_numeric($value)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);
        }

        return null;
    }
}
