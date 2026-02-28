<?php

namespace Platform\Brands\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Platform\Brands\Models\BrandsSeoBoard;
use Platform\Brands\Services\SeoClusteringService;
use Platform\Core\Models\User;

class AutoClusterSeoKeywordsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 1;

    public function __construct(
        private int $boardId,
        private int $userId,
        private int $minOverlap = 3,
        private int $maxKeywords = 300,
    ) {}

    public function handle(): void
    {
        $board = BrandsSeoBoard::find($this->boardId);

        if (!$board) {
            Log::warning('[AutoClusterSeoKeywordsJob] Board not found', [
                'board_id' => $this->boardId,
            ]);
            return;
        }

        $user = User::find($this->userId);

        if (!$user) {
            Log::warning('[AutoClusterSeoKeywordsJob] User not found', [
                'user_id' => $this->userId,
            ]);
            $board->update([
                'clustering_status' => 'failed',
                'clustering_result' => ['error' => 'User not found'],
                'clustering_completed_at' => now(),
            ]);
            return;
        }

        $board->update([
            'clustering_status' => 'processing',
            'clustering_started_at' => now(),
        ]);

        try {
            $clusteringService = app(SeoClusteringService::class);
            $result = $clusteringService->clusterBySerp($board, $user, $this->minOverlap, $this->maxKeywords);

            if (isset($result['error'])) {
                $board->update([
                    'clustering_status' => 'failed',
                    'clustering_result' => $result,
                    'clustering_completed_at' => now(),
                ]);

                Log::warning('[AutoClusterSeoKeywordsJob] Clustering failed with error', [
                    'board_id' => $this->boardId,
                    'error' => $result['error'],
                ]);
                return;
            }

            $board->update([
                'clustering_status' => 'completed',
                'clustering_result' => $result,
                'clustering_completed_at' => now(),
            ]);

            Log::info('[AutoClusterSeoKeywordsJob] Clustering completed', [
                'board_id' => $this->boardId,
                'clusters_created' => $result['clusters_created'],
                'keywords_clustered' => $result['keywords_clustered'],
                'cost_cents' => $result['cost_cents'],
            ]);
        } catch (\Throwable $e) {
            $board->update([
                'clustering_status' => 'failed',
                'clustering_result' => ['error' => $e->getMessage()],
                'clustering_completed_at' => now(),
            ]);

            Log::error('[AutoClusterSeoKeywordsJob] Job failed', [
                'board_id' => $this->boardId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('[AutoClusterSeoKeywordsJob] Job failed permanently', [
            'board_id' => $this->boardId,
            'error' => $e->getMessage(),
        ]);

        BrandsSeoBoard::where('id', $this->boardId)->update([
            'clustering_status' => 'failed',
            'clustering_result' => json_encode(['error' => $e->getMessage()]),
            'clustering_completed_at' => now(),
        ]);
    }
}
