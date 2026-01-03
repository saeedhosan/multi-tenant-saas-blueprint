<?php

namespace App\Jobs\Concerns;


trait ForeSyncToAsync
{
    /**
     * Force the sync connection to database
     */
    public function viaConnection(): void
    {
        if (config('queue.default') === 'sync') {

            $this->connection = config('queue.async'); // redis, database, etc.
        }
    }
}