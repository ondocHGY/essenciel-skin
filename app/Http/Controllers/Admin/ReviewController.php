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
            'product_id' => 'required|exists:products,id',
            'platform' => 'required|string|max:50',
        ]);

        $file = $request->file('file');
        $productId = $request->product_id;
        $platform = $request->platform;

        try {
            $spreadsheet = IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // 첫 번째 행은 헤더
            $headers = array_map('trim', array_map('strval', $rows[0] ?? []));
            $headerMap = $this->mapHeaders($headers);

            if (!isset($headerMap['content'])) {
                return back()->withErrors(['file' => '리뷰 내용 컬럼을 찾을 수 없습니다. (댓글, 내용, content, review 등)']);
            }

            $added = 0;
            $updated = 0;
            $skipped = 0;

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

                // 데이터 추출
                $reviewData = [
                    'product_id' => $productId,
                    'platform' => $platform,
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

                $reviewData['external_id'] = $externalId;

                // 기존 리뷰 확인
                $existing = ProductReview::where('platform', $platform)
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
     * 헤더 매핑
     */
    private function mapHeaders(array $headers): array
    {
        $map = [];

        $mappings = [
            'content' => ['댓글', '내용', 'content', 'review', '리뷰', 'レビュー内容', '리뷰내용'],
            'rating' => ['평점', '만족도', 'rating', 'score', '점수', '評価', '별점'],
            'author' => ['작성자', '작성자ID', 'author', 'user', 'name', '購入者', '닉네임'],
            'reviewed_at' => ['작성일', '등록일', 'date', '날짜', '日付', '登録日'],
            'external_id' => ['리뷰ID', '상품평번호', 'id', 'review_id', '번호', '상품평번호_h'],
        ];

        foreach ($headers as $index => $header) {
            $headerLower = mb_strtolower(trim($header));

            foreach ($mappings as $field => $keywords) {
                foreach ($keywords as $keyword) {
                    if (mb_strtolower($keyword) === $headerLower || str_contains($headerLower, mb_strtolower($keyword))) {
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
        $formats = ['Y-m-d', 'Y/m/d', 'Y.m.d', 'Y-m-d H:i:s'];

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
