<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\QrGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(
        private QrGeneratorService $qrGeneratorService
    ) {}

    public function index()
    {
        $products = Product::latest()->paginate(20);
        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:products,code',
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'efficacy_type' => 'nullable|string|in:moisture,elasticity,tone,pore,wrinkle',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'nullable|string|max:255',
            'base_curve' => 'required|array',
            'base_curve.moisture' => 'required|array|size:5',
            'base_curve.elasticity' => 'required|array|size:5',
            'base_curve.tone' => 'required|array|size:5',
            'base_curve.pore' => 'required|array|size:5',
            'base_curve.wrinkle' => 'required|array|size:5',
        ]);

        // 기본 효능 타입 설정
        $validated['efficacy_type'] = $validated['efficacy_type'] ?? 'moisture';

        // 빈 성분 필터링
        if (isset($validated['ingredients'])) {
            $validated['ingredients'] = array_values(array_filter($validated['ingredients'], fn($v) => !empty(trim($v))));
        }

        // 이미지 업로드 처리
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        // QR 코드 자동 생성
        $this->qrGeneratorService->generate($product);

        return redirect()->route('admin.products.index')
            ->with('success', '제품이 등록되었습니다.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:products,code,' . $product->id,
            'name' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'efficacy_type' => 'nullable|string|in:moisture,elasticity,tone,pore,wrinkle',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'nullable|string|max:255',
            'efficacy_phases' => 'nullable|array',
            'efficacy_phases.phase1' => 'nullable|string',
            'efficacy_phases.phase2' => 'nullable|string',
            'efficacy_phases.phase3' => 'nullable|string',
            'efficacy_milestones' => 'nullable|array',
            'efficacy_milestones.*' => 'nullable|string',
            'efficacy_metrics' => 'nullable|array',
            'efficacy_metrics.name' => 'nullable|string|max:255',
            'efficacy_metrics.unit' => 'nullable|string|max:50',
            'efficacy_metrics.baseline_min' => 'nullable|numeric',
            'efficacy_metrics.baseline_max' => 'nullable|numeric',
            'efficacy_metrics.target_improvement' => 'nullable|numeric',
            'efficacy_metrics.description' => 'nullable|string|max:500',
            'intro_metrics' => 'nullable|array',
            'intro_metrics.*.name' => 'nullable|string|max:255',
            'intro_metrics.*.value' => 'nullable|integer|min:0|max:5',
            'intro_metrics.*.color' => 'nullable|string|max:50',
            'intro_summary' => 'nullable|array',
            'intro_summary.*' => 'nullable|string|max:500',
            'intro_review_count' => 'nullable|integer|min:0',
        ]);

        // 기본 효능 타입 설정
        $validated['efficacy_type'] = $validated['efficacy_type'] ?? $product->efficacy_type ?? 'moisture';

        // 빈 성분 필터링
        if (isset($validated['ingredients'])) {
            $validated['ingredients'] = array_values(array_filter($validated['ingredients'], fn($v) => !empty(trim($v))));
        } else {
            $validated['ingredients'] = [];
        }

        // 효능 단계 설명 처리 (빈 값 필터링)
        if (isset($validated['efficacy_phases'])) {
            $validated['efficacy_phases'] = array_filter($validated['efficacy_phases'], fn($v) => !empty(trim($v)));
            if (empty($validated['efficacy_phases'])) {
                $validated['efficacy_phases'] = null;
            }
        }

        // 마일스톤 라벨 처리 (빈 값 필터링)
        if (isset($validated['efficacy_milestones'])) {
            $validated['efficacy_milestones'] = array_filter($validated['efficacy_milestones'], fn($v) => !empty(trim($v)));
            if (empty($validated['efficacy_milestones'])) {
                $validated['efficacy_milestones'] = null;
            } else {
                $validated['efficacy_milestones'] = array_values($validated['efficacy_milestones']);
            }
        }

        // 효능 측정 기준값 처리 (빈 값 필터링)
        if (isset($validated['efficacy_metrics'])) {
            $validated['efficacy_metrics'] = array_filter($validated['efficacy_metrics'], fn($v) => $v !== '' && $v !== null);
            if (empty($validated['efficacy_metrics'])) {
                $validated['efficacy_metrics'] = null;
            }
        }

        // 제품 소개 페이지 지표 처리 (빈 이름 필터링)
        if (isset($validated['intro_metrics'])) {
            $filtered = array_filter($validated['intro_metrics'], fn($v) => !empty(trim($v['name'] ?? '')));
            $validated['intro_metrics'] = empty($filtered) ? null : array_values($filtered);
        }

        // 제품 소개 페이지 요약 처리 (빈 값 필터링)
        if (isset($validated['intro_summary'])) {
            $filtered = array_filter($validated['intro_summary'], fn($v) => !empty(trim($v)));
            $validated['intro_summary'] = empty($filtered) ? null : array_values($filtered);
        }

        // 리뷰 수 처리
        if (empty($validated['intro_review_count'])) {
            $validated['intro_review_count'] = null;
        }

        // 이미지 업로드 처리
        if ($request->hasFile('image')) {
            // 기존 이미지 삭제
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // 이미지 삭제 요청
        if ($request->input('remove_image') === '1' && $product->image) {
            Storage::disk('public')->delete($product->image);
            $validated['image'] = null;
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', '제품이 수정되었습니다.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', '제품이 삭제되었습니다.');
    }

    public function generateQR(Product $product)
    {
        $path = $this->qrGeneratorService->generate($product);

        return response()->json([
            'success' => true,
            'path' => $path,
            'url' => asset('storage/' . $path),
        ]);
    }
}
