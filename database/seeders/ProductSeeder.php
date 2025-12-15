<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'code' => 'PROD-001',
                'name' => '하이드라 부스트 세럼',
                'brand' => 'Essenciel',
                'category' => '세럼',
                'ingredients' => ['히알루론산', '나이아신아마이드', '판테놀', '세라마이드'],
                'base_curve' => [
                    'moisture' => [15, 35, 55, 75, 90],
                    'elasticity' => [8, 20, 38, 58, 75],
                    'tone' => [10, 25, 42, 62, 80],
                    'pore' => [5, 15, 28, 45, 60],
                    'wrinkle' => [5, 12, 25, 40, 55],
                ],
            ],
            [
                'code' => 'PROD-002',
                'name' => '리페어 나이트 크림',
                'brand' => 'Essenciel',
                'category' => '크림',
                'ingredients' => ['레티놀', '펩타이드', '스쿠알란', '시어버터'],
                'base_curve' => [
                    'moisture' => [12, 28, 48, 68, 85],
                    'elasticity' => [10, 25, 45, 65, 82],
                    'tone' => [8, 22, 40, 60, 78],
                    'pore' => [6, 18, 32, 50, 65],
                    'wrinkle' => [8, 20, 38, 58, 75],
                ],
            ],
            [
                'code' => 'PROD-003',
                'name' => '비타민C 브라이트닝 앰플',
                'brand' => 'Essenciel',
                'category' => '앰플',
                'ingredients' => ['비타민C', '알부틴', '글루타치온', '비타민E'],
                'base_curve' => [
                    'moisture' => [10, 22, 38, 55, 70],
                    'elasticity' => [6, 15, 30, 48, 65],
                    'tone' => [15, 35, 55, 75, 92],
                    'pore' => [8, 20, 35, 52, 68],
                    'wrinkle' => [5, 15, 28, 45, 60],
                ],
            ],
        ];

        foreach ($products as $productData) {
            Product::create($productData);
        }
    }
}
