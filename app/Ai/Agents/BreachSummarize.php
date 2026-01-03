<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agent;
use App\Models\AiPrompt;

class BreachSummarize extends Agent
{
    /** {@inheritDoc} */
    protected string $name = 'Breach summarize';

    /**
     * Get the prompt from database
     */
    protected ?object $ai_prompt = null;

    /** {@inheritDoc} */
    public function instructions(): string
    {

        $system_prompt = 'You are a cybersecurity content writer. Summarize the user-provided breach text in 1-3 short, neutral sentences, using plain language, avoiding any mention of data sources or services, and removing references.';

        $databasePrompt = $this->ai_prompt?->prompt ?? null;

        return $databasePrompt ?: $system_prompt;
    }
}
