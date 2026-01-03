<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Concerns\ForeSyncToAsync;
use App\Models\Breach;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AIBreachSummaryJob implements ShouldQueue
{
    use Queueable, ForeSyncToAsync;

    /**
     * Create a new job instance.
     */
    public function __construct(public Breach $breach)
    {
        // $this->onQueue('ai');
    }

    /**
     * Execute the job.
     */
    public function handle():void
    {
        // Refresh model to get latest data
        $this->breach->refresh();

        if (empty($this->breach->description) && ! empty($this->breach->summary)) {
            return;
        }

        // execute the AI agent to generate summary
        // placeholder for now - replace with actual agent call

        $summary = 'AI-generated breach summary placeholder.';

        // generate ai description
        $this->breach->update(['summary' => $summary]);

        // next to clean cache for this breach
        // clear pdf preview cache
        // clear breach caches to response if exists
    }
}
