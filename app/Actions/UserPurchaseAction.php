<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Plan;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\SubscriptionBuilder;

class UserPurchaseAction
{
    /**
     * Create a new class instance.
     */
    public function __construct(public bool $isTrial = false)
    {
        //
    }

    /**
     * Invoke the class instance.
     */
    public function handle(Plan $plan): SubscriptionBuilder
    {
        $this->isTrial = false;
        $user          = Auth::user();
        $subscription  = $user->newSubscription('company', $plan->stripe_price_id);

        if ($plan->price <= 0) {

            $subscription->trialDays(config('subscription.trial_days'))->create();

            $user->update(['plan_id' => $plan->id]);

            $this->isTrial = true;
        }

        return $subscription;
    }
}
