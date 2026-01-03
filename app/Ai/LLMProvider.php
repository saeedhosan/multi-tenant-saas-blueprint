<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * Minimal AI client stub to avoid private dependencies.
 */
class LLMProvider
{
    /**
     * @var array<class-string<CreateResponse>, CreateResponse>
     */
    protected static array $fakes = [];

    /**
     * Allow tests to inject canned responses.
     *
     * @param  array<class-string<CreateResponse>, CreateResponse>  $responses
     */
    public static function fake(array $responses = []): void
    {
        self::$fakes = $responses;
    }

    public static function chat(): self
    {
        return new self();
    }

    public function create(array $parameters): CreateResponse
    {
        $response = self::$fakes[CreateResponse::class] ?? null;

        if ($response instanceof CreateResponse) {
            return $response;
        }

        return CreateResponse::fake([
            'model'   => $parameters['model'] ?? 'stub-model',
            'choices' => [
                [
                    'index'   => 0,
                    'message' => [
                        'role'    => 'assistant',
                        'content' => 'Placeholder AI response.',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
        ]);
    }
}
