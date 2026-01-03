<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Call;
use App\Models\CallSession;
use App\Models\Campaign;
use App\Models\LeadList;
use App\Support\Number;
use Illuminate\Support\Facades\DB;
use Twilio\Rest\Api\V2010\Account\CallInstance;
use Throwable;

final class CallService
{
    private ?int $callCode = null;

    public function __construct(
        private readonly TwilioService $twilioService,
        private readonly TemplateService $templateService,
    ) {
    }

    /**
     * Generate a unique 4-digit call code.
     */
    public function generateCallCode(): int
    {
        do {
            $code = Number::randomLength(4);
        } while (
            Call::query()
                ->where('call_code', $code)
                ->exists()
        );

        return $this->callCode = $code;
    }

    /**
     * Start a Twilio call for a campaign lead.
     */
    public function start(Campaign $campaign, LeadList $leadList): ?CallInstance
    {
        $from = config('twilio.phone_number');

        if (! is_string($from) || $from === '') {
            logger()->error('Twilio phone_number configuration is missing.');
            return null;
        }

        $this->generateCallCode();

        try {
            $call = $this->twilioService->initCall(
                from: $from,
                to: $leadList->phone,
                record: (bool) $campaign->options->get('allow_record')
            );
        } catch (Throwable $exception) {
            $this->logTwilioError($exception, $campaign, $leadList);
            return null;
        }

        $this->persistCallData($campaign, $leadList, $call);

        return $call;
    }

    /**
     * Safely handle sync call without breaking webhook flow.
     */
    public function handleSyncCall(?Call $call): void
    {
        if (! $call instanceof Call) {
            return;
        }

        try {
            $this->initSyncCall($call);
        } catch (Throwable $exception) {
            logger()->error('Sync call failed.', [
                'call_id' => $call->id,
                'error'   => $exception->getMessage(),
            ]);
        }
    }

    /**
     * Start next call when current call is completed.
     */
    public function initSyncCall(Call $call): void
    {
        if (! $this->canInitiateNextCall($call)) {
            return;
        }

        $lead = $this->getNextPendingLead($call->campaign);

        if ($lead instanceof LeadList) {
            // dispatch(new StartNextCallJob($call->campaign, $lead));
        }
    }

    /* -----------------------------------------------------------------
     | Internal helpers
     | -----------------------------------------------------------------
     */
    private function persistCallData(Campaign $campaign, LeadList $leadList, CallInstance $call): void {
        DB::transaction(function () use ($campaign, $leadList, $call): void {

            Call::query()->create([
                'user_id'   => $campaign->user_id,
                'campaign_id' => $campaign->id,
                'call_id'   => $call->sid,
                'number'    => $call->to,
                'status'    => $call->status,
                'call_code' => $this->callCode,
                //...
            ]);

            CallSession::query()->create([
                'call_sid' => $call->sid,
                'settings' => $this->buildCallSettings($campaign),
                'webhooks' => $this->twilioService->webhookRouting(),
                //...
            ]);
        });
    }

    private function buildCallSettings(Campaign $campaign): array
    {
        return [
            'company'         => $campaign->organization->name,
            'from_number'     => config('twilio.phone_number'),
            'transfer_number' => config('twilio.transfer_number'),
            'call_code'       => $this->callCode,
            //...
        ];
    }

    private function canInitiateNextCall(Call $call): bool
    {
        return
            $call->status === Call::STATUS_COMPLETED &&
            $call->campaign !== null &&
            $call->campaign->status === Campaign::STATUS_IN_PROGRESS &&
            ! $call->campaign->options->get('allow_delay');
    }

    private function getNextPendingLead(Campaign $campaign): ?LeadList
    {
        $leadIds = $campaign->leads()->pluck('id');

        return LeadList::query()
            ->whereIn('lead_id', $leadIds)
            ->where('call_status', LeadList::STATUS_PENDING)
            ->first();
    }

    private function logTwilioError(Throwable $exception, Campaign $campaign, LeadList $leadList): void {
        logger()->error('Failed to initiate Twilio call.', [
            'campaign_id' => $campaign->id,
            'error'       => $exception->getMessage(),
            //...
        ]);
    }
}
