<?php

namespace App\Console\Commands;

use App\Services\Review\ReviewSyncService;
use Illuminate\Console\Command;

class SyncReviewsCommand extends Command
{
    protected $signature = 'reviews:sync
                            {--hours=3 : 동기화 간격 (시간)}
                            {--product= : 특정 제품 ID만 동기화}
                            {--force : 시간 관계없이 강제 동기화}';

    protected $description = '입점 플랫폼에서 제품 리뷰를 동기화합니다';

    public function handle(ReviewSyncService $service): int
    {
        $productId = $this->option('product');
        $hours = $this->option('force') ? 0 : (int) $this->option('hours');

        $this->info('리뷰 동기화를 시작합니다...');

        if ($productId) {
            $results = $service->syncProduct((int) $productId);
            $this->displayProductResults($results);
        } else {
            $results = $service->syncAll($hours);
            $this->displayResults($results);
        }

        return Command::SUCCESS;
    }

    protected function displayResults(array $results): void
    {
        $this->newLine();
        $this->info("동기화 완료!");
        $this->table(
            ['항목', '값'],
            [
                ['전체', $results['total']],
                ['성공', $results['success']],
                ['실패', $results['failed']],
            ]
        );

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->error('오류 목록:');
            foreach ($results['errors'] as $error) {
                $this->line("  - [{$error['platform']}] {$error['error']}");
            }
        }
    }

    protected function displayProductResults(array $results): void
    {
        $this->newLine();
        foreach ($results as $platform => $result) {
            if ($result['success']) {
                $this->info("[$platform] 동기화 성공");
            } else {
                $this->error("[$platform] 실패: " . ($result['error'] ?? 'Unknown error'));
            }
        }
    }
}
