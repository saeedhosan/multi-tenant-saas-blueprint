<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ForeSyncToAsync;
use App\Models\Breach;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AiBreachBlogJob implements ShouldQueue
{
    use Queueable, ForeSyncToAsync;

    /**
     * Create a new job instance.
     */
    public function __construct(public Breach $breach)
    {
        $this->viaConnection();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Refresh model to get latest data
        $this->breach->refresh();

        // execute the AI agent to create blog content
        // placeholder for now - replace with actual agent call

        // next to clean cache for this breach
        // clear pdf preview cache
        // clear breaches caches if exists
    }


}
