<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Ai\Agent;
use App\Ai\CreateResponse;
use App\Ai\LLMProvider;
use Tests\TestCase;

class AgentTest extends TestCase
{
    /**
     * make fake Ai Usage
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock globally for all tests to prevent side effects
        //...
    }

    public function test_it_adds_response_format_when_schema_exists(): void
    {
        $fakeResponse = $this->makeFakeResponse('Structured response.');
        LLMProvider::fake([CreateResponse::class => $fakeResponse]);

        $agent = $this->makeAgent([
            'output_schema' => ['type' => 'json'],
        ])->execute();

        $this->assertInstanceOf(CreateResponse::class, $agent->response());
        $this->assertSame('Structured response.', $agent->output());
        $this->assertSame(['type' => 'json'], $agent->outputSchema());
    }

    public function test_it_executes_and_stores_LLMProvider_response(): void
    {
        $fakeResponse = $this->makeFakeResponse('AI helps automate tasks.');

        LLMProvider::fake([
            // The fake matches the CreateResponse class
            CreateResponse::class => $fakeResponse,
        ]);

        $agent = $this->makeAgent();
        $agent->execute();

        $this->assertSame('AI helps automate tasks.', $agent->output());
        $this->assertInstanceOf(CreateResponse::class, $agent->response());
    }

    public function test_it_returns_model_prompt_and_instructions(): void
    {
        $agent = $this->makeAgent([
            'model'        => 'gpt-5-turbo',
            'instructions' => 'Be concise.',
            'prompt'       => 'Define AI.',
        ]);

        $this->assertSame('gpt-5-turbo', $agent->model());
        $this->assertSame('Be concise.', $agent->instructions());
        $this->assertSame('Define AI.', $agent->prompt());
    }

    public function test_it_returns_correct_message_format(): void
    {
        $agent = $this->makeAgent([
            'instructions' => 'Act as an expert.',
            'prompt'       => 'What is machine learning?',
        ]);

        $messages = $agent->messages();

        $this->assertCount(2, $messages);
        $this->assertEquals('system', $messages[0]['role']);
        $this->assertEquals('Act as an expert.', $messages[0]['content']);
        $this->assertEquals('user', $messages[1]['role']);
        $this->assertEquals('What is machine learning?', $messages[1]['content']);
    }

    public function test_it_lazy_loads_response_if_not_previously_executed(): void
    {
        $fakeResponse = $this->makeFakeResponse('Lazy load OK');
        LLMProvider::fake([CreateResponse::class => $fakeResponse]);

        $agent    = $this->makeAgent();
        $response = $agent->response();

        $this->assertInstanceOf(CreateResponse::class, $response);
        $this->assertSame('Lazy load OK', $agent->output());
    }

    public function test_it_returns_empty_output_message(): void
    {
        $fakeResponse = $this->makeFakeResponse('');
        LLMProvider::fake([CreateResponse::class => $fakeResponse]);

        $agent = $this->makeAgent()->execute();

        $this->assertInstanceOf(CreateResponse::class, $agent->response());
        $this->assertSame('', $agent->output());
    }

    public function test_it_calls_on_complete_hook(): void
    {
        $fakeResponse = $this->makeFakeResponse('Hook triggered.');

        LLMProvider::fake([CreateResponse::class => $fakeResponse]);

        $agent = new class extends Agent
        {
            public bool $completed = false;

            protected function onComplete(CreateResponse $response): void
            {
                $this->completed = true;
            }
        };

        $agent->execute();

        $this->assertTrue($agent->completed);
        $this->assertInstanceOf(CreateResponse::class, $agent->response());
    }

    /**
     * Ensure outputSchema() defaults to empty array.
     */
    public function test_it_returns_empty_schema_by_default(): void
    {
        $agent = $this->makeAgent();
        $this->assertSame([], $agent->outputSchema());
    }

    /**
     * Helper: Create a fake Chat response using the official factory.
     */
    protected function makeFakeResponse(string $content = 'AI stands for Artificial Intelligence.'): CreateResponse
    {
        return CreateResponse::fake([
            'id'      => 'chatcmpl-test123',
            'object'  => 'chat.completion',
            'created' => now()->timestamp,
            'model'   => 'gpt-5-nano',
            'choices' => [
                [
                    'index'   => 0,
                    'message' => [
                        'role'    => 'assistant',
                        'content' => $content,
                    ],
                    'logprobs'      => null,
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens'     => 5,
                'completion_tokens' => 7,
                'total_tokens'      => 12,
            ]
        ]);
    }

    /**
     * Build a concrete version of the abstract Agent for testing.
     */
    protected function makeAgent(array $overrides = []): Agent
    {
        return new class($overrides) extends Agent
        {
            public function __construct(private array $overrides = [])
            {
                foreach ($overrides as $key => $value) {
                    $this->{$key} = $value;
                }
            }
        };
    }
}
