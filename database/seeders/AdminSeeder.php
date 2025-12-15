<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => 'admin@essenciel.co.kr'],
            [
                'name' => '관리자',
                'password' => Hash::make('7856pass!!'),
            ]
        );
    }
}
