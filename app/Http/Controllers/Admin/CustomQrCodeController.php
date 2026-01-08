<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QrGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomQrCodeController extends Controller
{
    public function __construct(
        private QrGeneratorService $qrService
    ) {}

    public function index()
    {
        return view('admin.custom-qr.index');
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
}