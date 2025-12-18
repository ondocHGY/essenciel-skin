<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductIngredient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductIngredientController extends Controller
{
    /**
     * 제품의 성분 목록
     */
    public function index(Product $product)
    {
        $ingredients = $product->productIngredients()->ordered()->get();
        return view('admin.product-ingredients.index', compact('product', 'ingredients'));
    }

    /**
     * 성분 생성 폼
     */
    public function create(Product $product)
    {
        return view('admin.product-ingredients.create', compact('product'));
    }

    /**
     * 성분 저장
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'percentage' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // 이미지 업로드 처리
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('ingredients', 'public');
        }

        // 빈 태그 필터링
        if (isset($validated['tags'])) {
            $validated['tags'] = array_values(array_filter($validated['tags'], fn($v) => !empty(trim($v))));
            if (empty($validated['tags'])) {
                $validated['tags'] = null;
            }
        }

        // 활성화 체크박스 처리
        $validated['is_active'] = $request->has('is_active');

        // 정렬 순서 기본값
        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = $product->productIngredients()->max('sort_order') + 1;
        }

        $product->productIngredients()->create($validated);

        return redirect()->route('admin.products.ingredients.index', $product)
            ->with('success', '성분이 추가되었습니다.');
    }

    /**
     * 성분 수정 폼
     */
    public function edit(Product $product, ProductIngredient $ingredient)
    {
        return view('admin.product-ingredients.edit', compact('product', 'ingredient'));
    }

    /**
     * 성분 수정
     */
    public function update(Request $request, Product $product, ProductIngredient $ingredient)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'percentage' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'nullable|string|max:50',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // 이미지 업로드 처리
        if ($request->hasFile('image')) {
            // 기존 이미지 삭제
            if ($ingredient->image) {
                Storage::disk('public')->delete($ingredient->image);
            }
            $validated['image'] = $request->file('image')->store('ingredients', 'public');
        }

        // 이미지 삭제 요청
        if ($request->input('remove_image') === '1' && $ingredient->image) {
            Storage::disk('public')->delete($ingredient->image);
            $validated['image'] = null;
        }

        // 빈 태그 필터링
        if (isset($validated['tags'])) {
            $validated['tags'] = array_values(array_filter($validated['tags'], fn($v) => !empty(trim($v))));
            if (empty($validated['tags'])) {
                $validated['tags'] = null;
            }
        }

        // 활성화 체크박스 처리
        $validated['is_active'] = $request->has('is_active');

        $ingredient->update($validated);

        return redirect()->route('admin.products.ingredients.index', $product)
            ->with('success', '성분이 수정되었습니다.');
    }

    /**
     * 성분 삭제
     */
    public function destroy(Product $product, ProductIngredient $ingredient)
    {
        // 이미지 삭제
        if ($ingredient->image) {
            Storage::disk('public')->delete($ingredient->image);
        }

        $ingredient->delete();

        return redirect()->route('admin.products.ingredients.index', $product)
            ->with('success', '성분이 삭제되었습니다.');
    }

    /**
     * 성분 순서 변경 (AJAX)
     */
    public function reorder(Request $request, Product $product)
    {
        $validated = $request->validate([
            'order' => 'required|array',
            'order.*' => 'integer|exists:product_ingredients,id',
        ]);

        foreach ($validated['order'] as $index => $id) {
            ProductIngredient::where('id', $id)
                ->where('product_id', $product->id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
