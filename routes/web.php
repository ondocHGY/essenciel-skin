<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\SurveyController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SurveyController as AdminSurveyController;
use App\Http\Controllers\Admin\SurveyQuestionController as AdminSurveyQuestionController;
use App\Http\Controllers\Admin\ProductIngredientController as AdminProductIngredientController;
use App\Http\Controllers\Admin\CustomQrCodeController as AdminCustomQrCodeController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\ScraperController as AdminScraperController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(404, '잘못된 경로입니다. QR코드를 스캔하여 접속해주세요.');
});

// 제품 관련 라우트
Route::get('/p/{code}', [ProductController::class, 'show'])->name('product.show');
Route::get('/p/{code}/survey', [SurveyController::class, 'index'])->name('survey.index');
Route::post('/p/{code}/survey', [SurveyController::class, 'store'])->name('survey.store');
Route::get('/p/{code}/result', [ResultController::class, 'show'])->name('result.show');

// 공유 결과 라우트
Route::get('/share/{token}', [ResultController::class, 'share'])->name('result.share');

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

        // 제품 성분 관리
        Route::get('/products/{product}/ingredients', [AdminProductIngredientController::class, 'index'])->name('products.ingredients.index');
        Route::get('/products/{product}/ingredients/create', [AdminProductIngredientController::class, 'create'])->name('products.ingredients.create');
        Route::post('/products/{product}/ingredients', [AdminProductIngredientController::class, 'store'])->name('products.ingredients.store');
        Route::get('/products/{product}/ingredients/{ingredient}/edit', [AdminProductIngredientController::class, 'edit'])->name('products.ingredients.edit');
        Route::put('/products/{product}/ingredients/{ingredient}', [AdminProductIngredientController::class, 'update'])->name('products.ingredients.update');
        Route::delete('/products/{product}/ingredients/{ingredient}', [AdminProductIngredientController::class, 'destroy'])->name('products.ingredients.destroy');
        Route::post('/products/{product}/ingredients/reorder', [AdminProductIngredientController::class, 'reorder'])->name('products.ingredients.reorder');
        Route::post('/products/{product}/ingredients/positions', [AdminProductIngredientController::class, 'updatePositions'])->name('products.ingredients.positions');

        // 설문 결과 관리
        Route::get('/surveys', [AdminSurveyController::class, 'index'])->name('surveys.index');
        Route::get('/surveys/export', [AdminSurveyController::class, 'export'])->name('surveys.export');
        Route::get('/surveys/{result}', [AdminSurveyController::class, 'show'])->name('surveys.show');
        Route::delete('/surveys/{result}', [AdminSurveyController::class, 'destroy'])->name('surveys.destroy');

        // 설문 질문 관리
        Route::get('/survey-questions', [AdminSurveyQuestionController::class, 'index'])->name('survey-questions.index');
        Route::get('/survey-questions/create', [AdminSurveyQuestionController::class, 'create'])->name('survey-questions.create');
        Route::post('/survey-questions', [AdminSurveyQuestionController::class, 'store'])->name('survey-questions.store');
        Route::get('/survey-questions/{surveyQuestion}/edit', [AdminSurveyQuestionController::class, 'edit'])->name('survey-questions.edit');
        Route::put('/survey-questions/{surveyQuestion}', [AdminSurveyQuestionController::class, 'update'])->name('survey-questions.update');
        Route::delete('/survey-questions/{surveyQuestion}', [AdminSurveyQuestionController::class, 'destroy'])->name('survey-questions.destroy');
        Route::post('/survey-questions/update-order', [AdminSurveyQuestionController::class, 'updateOrder'])->name('survey-questions.update-order');

        // 커스텀 QR 코드 생성
        Route::get('/custom-qr', [AdminCustomQrCodeController::class, 'index'])->name('custom-qr.index');
        Route::post('/custom-qr/generate', [AdminCustomQrCodeController::class, 'generate'])->name('custom-qr.generate');

        // 리뷰 관리
        Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
        Route::get('/reviews/upload', [AdminReviewController::class, 'uploadForm'])->name('reviews.upload');
        Route::post('/reviews/upload', [AdminReviewController::class, 'upload'])->name('reviews.upload.submit');
        Route::get('/reviews/download-sample', [AdminReviewController::class, 'downloadSample'])->name('reviews.download-sample');
        Route::get('/reviews/export', [AdminReviewController::class, 'export'])->name('reviews.export');
        Route::get('/reviews/{review}/edit', [AdminReviewController::class, 'edit'])->name('reviews.edit');
        Route::put('/reviews/{review}', [AdminReviewController::class, 'update'])->name('reviews.update');
        Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');
        Route::post('/reviews/{review}/toggle-visibility', [AdminReviewController::class, 'toggleVisibility'])->name('reviews.toggle-visibility');

        // 스크래퍼 관리
        Route::get('/scraper', [AdminScraperController::class, 'index'])->name('scraper.index');
        Route::post('/scraper/sync', [AdminScraperController::class, 'sync'])->name('scraper.sync');
        Route::post('/scraper/cookies/{platform}', [AdminScraperController::class, 'uploadCookie'])->name('scraper.upload-cookie');
        Route::delete('/scraper/cookies/{platform}', [AdminScraperController::class, 'deleteCookie'])->name('scraper.delete-cookie');
        Route::post('/scraper/sources', [AdminScraperController::class, 'storeSource'])->name('scraper.store-source');
        Route::post('/scraper/sources/{source}/toggle', [AdminScraperController::class, 'toggleSource'])->name('scraper.toggle-source');
        Route::delete('/scraper/sources/{source}', [AdminScraperController::class, 'destroySource'])->name('scraper.destroy-source');
        Route::post('/scraper/match-reviews', [AdminScraperController::class, 'matchReviews'])->name('scraper.match-reviews');
    });
});
