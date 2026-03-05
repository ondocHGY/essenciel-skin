<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrGeneratorService
{
    public function generate(Product $product): string
    {
        $url = config('app.url') . '/p/' . $product->code;

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($url);

        $path = 'qrcodes/' . $product->code . '.png';

        Storage::disk('public')->put($path, $qrCode);

        $product->update(['qr_path' => $path]);

        return $path;
    }

    public function getQrUrl(Product $product): ?string
    {
        if ($product->qr_path) {
            return Storage::disk('public')->url($product->qr_path);
        }

        return null;
    }

    /**
     * 제품별 다국어 QR 코드 생성
     */
    public function generateForLocale(Product $product, string $locale): string
    {
        $baseUrl = config('app.url');
        $url = $locale === 'ko'
            ? "{$baseUrl}/p/{$product->code}"
            : "{$baseUrl}/{$locale}/p/{$product->code}";

        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($url);

        $path = "qrcodes/{$product->code}_{$locale}.png";

        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }

    /**
     * 커스텀 URL로 QR 코드 생성
     */
    public function generateFromUrl(string $url, ?string $filename = null): string
    {
        $qrCode = QrCode::format('png')
            ->size(300)
            ->margin(2)
            ->generate($url);

        $filename = $filename ?: 'custom_' . time() . '_' . uniqid();
        $path = 'qrcodes/' . $filename . '.png';

        Storage::disk('public')->put($path, $qrCode);

        return $path;
    }
}
