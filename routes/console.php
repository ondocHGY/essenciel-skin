<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 리뷰 동기화 스케줄 (3시간마다)
Schedule::command('reviews:sync --hours=3')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/review-sync.log'));
