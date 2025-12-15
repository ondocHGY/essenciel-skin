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
}
