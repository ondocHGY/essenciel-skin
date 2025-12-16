<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Admin\SurveyOptionController as AdminSurveyOptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(404, '잘못된 경로입니다. QR코드를 스캔하여 접속해주세요.');
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

        // 설문 옵션 관리
        Route::get('/survey-options', [AdminSurveyOptionController::class, 'index'])->name('survey-options.index');
        Route::get('/survey-options/create', [AdminSurveyOptionController::class, 'create'])->name('survey-options.create');
        Route::post('/survey-options', [AdminSurveyOptionController::class, 'storeCategory'])->name('survey-options.store');
        Route::get('/survey-options/{category}/edit', [AdminSurveyOptionController::class, 'edit'])->name('survey-options.edit');
        Route::put('/survey-options/{category}', [AdminSurveyOptionController::class, 'updateCategory'])->name('survey-options.update');
        Route::delete('/survey-options/{category}', [AdminSurveyOptionController::class, 'destroyCategory'])->name('survey-options.destroy');
        Route::post('/survey-options/{category}/options', [AdminSurveyOptionController::class, 'storeOption'])->name('survey-options.options.store');
        Route::put('/survey-options/options/{option}', [AdminSurveyOptionController::class, 'updateOption'])->name('survey-options.options.update');
        Route::delete('/survey-options/options/{option}', [AdminSurveyOptionController::class, 'destroyOption'])->name('survey-options.options.destroy');
        Route::post('/survey-options/{category}/reorder', [AdminSurveyOptionController::class, 'reorderOptions'])->name('survey-options.options.reorder');
        Route::post('/survey-options/reorder', [AdminSurveyOptionController::class, 'reorderCategories'])->name('survey-options.reorder');
    });
});
