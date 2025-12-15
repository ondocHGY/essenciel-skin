<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function show(Request $request, string $code)
    {
        $product = Product::where('code', $code)->firstOrFail();

        // 세션 ID 생성 (없으면)
        if (!$request->session()->has('skincare_session_id')) {
            $request->session()->put('skincare_session_id', Str::uuid()->toString());
        }

        return view('product.show', compact('product'));
    }
}
