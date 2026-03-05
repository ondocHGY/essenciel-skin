<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\QrGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomQrCodeController extends Controller
{
    public function __construct(
        private QrGeneratorService $qrService
    ) {}

    private const LOCALE_LABELS = [
        'ko' => '한국어',
        'en' => 'English',
        'ja' => '日本語',
        'zh' => '中文',
        'vi' => 'Tiếng Việt',
        'ar' => 'العربية',
    ];

    public function index()
    {
        $products = Product::orderBy('name')->get(['id', 'code', 'name']);

        return view('admin.custom-qr.index', compact('products'));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'filename' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
        ], [
            'url.required' => 'URL을 입력해주세요.',
            'url.url' => '올바른 URL 형식을 입력해주세요.',
            'filename.regex' => '파일명은 영문, 숫자, 언더스코어(_), 하이픈(-)만 사용 가능합니다.',
        ]);

        $path = $this->qrService->generateFromUrl(
            $request->input('url'),
            $request->input('filename')
        );

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => Storage::disk('public')->url($path),
        ]);
    }

    public function generateLocale(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'locales' => 'required|array|min:1',
            'locales.*' => 'string|in:ko,en,ja,zh,vi,ar',
        ], [
            'product_id.required' => '제품을 선택해주세요.',
            'locales.required' => '언어를 하나 이상 선택해주세요.',
        ]);

        $product = Product::findOrFail($request->input('product_id'));
        $locales = $request->input('locales');
        $baseUrl = config('app.url');

        $results = [];
        foreach ($locales as $locale) {
            $path = $this->qrService->generateForLocale($product, $locale);
            $embeddedUrl = $locale === 'ko'
                ? "{$baseUrl}/p/{$product->code}"
                : "{$baseUrl}/{$locale}/p/{$product->code}";

            $results[] = [
                'locale' => $locale,
                'label' => self::LOCALE_LABELS[$locale] ?? $locale,
                'url' => Storage::disk('public')->url($path),
                'path' => $path,
                'embedded_url' => $embeddedUrl,
            ];
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}