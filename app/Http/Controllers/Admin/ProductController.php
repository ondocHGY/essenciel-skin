<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\QrGeneratorService;
use Illuminate\Http\Request;

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
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'nullable|string|max:255',
            'base_curve' => 'required|array',
            'base_curve.moisture' => 'required|array|size:5',
            'base_curve.elasticity' => 'required|array|size:5',
            'base_curve.tone' => 'required|array|size:5',
            'base_curve.pore' => 'required|array|size:5',
            'base_curve.wrinkle' => 'required|array|size:5',
        ]);

        // 빈 성분 필터링
        if (isset($validated['ingredients'])) {
            $validated['ingredients'] = array_values(array_filter($validated['ingredients'], fn($v) => !empty(trim($v))));
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
            'ingredients' => 'nullable|array',
            'ingredients.*' => 'nullable|string|max:255',
            'base_curve' => 'required|array',
        ]);

        // 빈 성분 필터링
        if (isset($validated['ingredients'])) {
            $validated['ingredients'] = array_values(array_filter($validated['ingredients'], fn($v) => !empty(trim($v))));
        } else {
            $validated['ingredients'] = [];
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
