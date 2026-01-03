<?php

declare(strict_types=1);

namespace App\Actions;

use App\Jobs\AiBreachBlogJob;
use App\Jobs\AIBreachSummaryJob;
use App\Models\Breach;
use App\Models\User;
use App\Services\HibpApi;
use App\Responses\HibpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MonitorUserBreachAction
{
    /**
     * Handle the class action.
     */
    public function handle(string|User $email):int
    {
        return DB::transaction(function () use ($email): int {

            $user = $email instanceof User ? $email : User::findByEmail($email, true);

            if (empty($user?->email)) {
                return 0;
            }

            $now = now();

            $data = app(HibpApi::class)->breachedaccount($user->email)->collect();

            $ids = $data->map(function (HibpResponse $item) use ($now) {

                // Create (by unique 'name') or fetch
                $breach = Breach::query()->firstOrCreate(
                    ['name' => $item->name],
                    $item->toArray()
                );

                if ($breach->wasRecentlyCreated) {
                    DB::afterCommit(function () use ($breach): void {
                        dispatch(new AiBreachBlogJob($breach));
                        dispatch(new AIBreachSummaryJob($breach));
                    });
                } elseif ($breach->updated_at->lt($now->subDays(7))) {
                    $breach->update(
                        Arr::except($item->toArray(), ['name'])
                    );
                }

                return $breach->id;
            });

            $user?->breaches()->sync($ids);

            return (int) $user?->breaches()->count();
        });
    }
}
