<?php

declare(strict_types=1);

namespace App\Ai;


abstract class Agent
{
    /**
     * The agent name.
     */
    protected string $name = 'AI Agent';

    /**
     * The AI model to use.
     */
    protected string $model = 'gpt-5-nano';

    /**
     * The system instructions and context for the agent.
     */
    protected string $instructions = 'You are a helpful AI assistant.';

    /**
     * The user prompt or input for the agent.
     */
    protected string $prompt = 'What is AI?';

    /**
     * The memory storage type (e.g., 'session', 'persistent').
     */
    protected string $history = 'session';

    /**
     * The AI provider to use.
     */
    protected string $provider = 'openai';

    /**
     * The tools available to the agent.
     *
     * @var array<string, callable>
     */
    protected array $tools = [];

    /**
     * The messages for this agent.
     *
     * @return array<int, array{role: string, content: string}>|null
     */
    protected ?array $messages = null;

    /**
     * The output schema for structured responses.
     *
     * @return array<string, mixed>
     */
    protected array $output_schema = [];

    /**
     * Get the response from the agent.
     */
    protected ?CreateResponse $response = null;

    /**
     * Get the output schema for structured responses.
     *
     * @return array<string, mixed>
     */
    public function outputSchema()
    {
        return $this->output_schema;
    }

    /**
     * Get the messages for the agent.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function messages(): array
    {
        return $this->messages ?? [
            ['role' => 'system', 'content' => $this->instructions()],
            ['role' => 'user',   'content' => $this->prompt()],
        ];
    }

    /**
     * Get the AI model being used.
     */
    public function model(): string
    {
        return $this->model;
    }

    /**
     * Get the agent's instructions.
     */
    public function instructions(): string
    {
        return $this->instructions;
    }

    /**
     * Get the user prompt or input for the agent.
     */
    public function prompt(): string
    {
        return $this->prompt;
    }

    /**
     * Returns the last response from the AI.
     */
    public function response(): CreateResponse
    {
        if (! $this->response instanceof CreateResponse) {
            $this->handle();
        }

        assert($this->response instanceof CreateResponse);

        return $this->response;
    }

    /**
     * Get agent responsed output text.
     *
     * @return mixed The output content, or null if no response is available.
     */
    public function output()
    {
        return data_get($this->response(), 'choices.0.message.content');
    }

    /**
     * Execute the agent and return the response.
     */
    public function execute(): static
    {
        $this->handle();

        return $this;
    }

    /**
     * Hook any initialization logic.
     */
    protected function onInit(): void
    {
        // Optional subclass initialization
    }

    /**
     * Hook any completion logic.
     */
    protected function onComplete(CreateResponse $response): void
    {
        // Optional subclass completion logic
    }

    /**
     * Hook the response logic.
     */
    protected function onResponse(CreateResponse $response): void
    {
        // log the response or save to database
    }

    /**
     * Executes the agent and stores the response.
     */
    protected function handle(): void
    {
        $parameters = [
            'model'    => $this->model(),
            'messages' => $this->messages(),
        ];

        if ($schema = $this->outputSchema()) {
            $parameters['response_format'] = $schema;
        }

        $response = LLMProvider::chat()->create($parameters);

        $this->response = $response;

        $this->onResponse($response);

        $this->onComplete($response);
    }
}
