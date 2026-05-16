<?php

namespace App\Jobs;

use App\Services\BookRecommendationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAITask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(public array $data)
    {
    }

    public function handle(BookRecommendationService $recommendations): void
    {
        if (($this->data['feature'] ?? 'recommendation') === 'recommendation') {
            $recommendations->recommend(
                $this->data['query'] ?? '',
                $this->data['category_id'] ?? null,
                $this->data['user_id'] ?? null
            );
        }
    }
}
