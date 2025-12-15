<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// 제품 관련 라우트
Route::get('/p/{code}', [ProductController::class, 'show'])->name('product.show');
Route::get('/p/{code}/survey', [SurveyController::class, 'index'])->name('survey.index');
Route::post('/p/{code}/survey', [SurveyController::class, 'store'])->name('survey.store');
Route::get('/p/{code}/result', [ResultController::class, 'show'])->name('result.show');

// 관리자 라우트
Route::prefix('admin')->name('admin.')->group(function () {
    // 인증 라우트 (미들웨어 없음)
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    // 인증 필요한 라우트
    Route::middleware('admin.auth')->group(function () {
        // 대시보드
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // 제품 관리
        Route::get('/products', [AdminProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [AdminProductController::class, 'create'])->name('products.create');
        Route::post('/products', [AdminProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/qr', [AdminProductController::class, 'generateQR'])->name('products.generateQR');

        // 설문 결과 관리
        Route::get('/surveys', [AdminSurveyController::class, 'index'])->name('surveys.index');
        Route::get('/surveys/export', [AdminSurveyController::class, 'export'])->name('surveys.export');
        Route::get('/surveys/{result}', [AdminSurveyController::class, 'show'])->name('surveys.show');
        Route::delete('/surveys/{result}', [AdminSurveyController::class, 'destroy'])->name('surveys.destroy');
    });
});
