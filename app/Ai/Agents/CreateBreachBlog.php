<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\Ai\Agent;
use App\Ai\Schema\Schema;

class CreateBreachBlog extends Agent
{
    /** {@inheritDoc} */
    protected string $name = 'Breach Blog';

    /**
     * Breach payload to generate the blog from.
     *
     * @var array<string, mixed>
     */
    protected array $breach = [];

    /**
     * Optional custom system prompt.
     */
    protected ?string $instructionsTemplate = null;


    /** {@inheritDoc} */
    public function instructions(): string
    {

        return $this->instructionsTemplate
            ?? 'You are a cybersecurity content writer. Generate a concise blog post based on the provided breach facts.';
    }

    /** {@inheritDoc} */
    public function prompt(): string
    {
        $allowed = ['name', 'description', 'breach_date', 'data_classes'];
        $data    = array_intersect_key($this->breach, array_flip($allowed));

        return json_encode($data, JSON_THROW_ON_ERROR);
    }

    /** {@inheritDoc} */
    public function outputSchema(): array
    {
        return Schema::object('Get the blog data')
            ->required(['title', 'tl_dr', 'tags', 'sections', 'questions'])
            ->property('title', Schema::string('55-70 chars, natural, include primary keyword early'))
            ->property('tl_dr', Schema::string('Get the blog full TL;DR / Key Takeaways')->min(100))
            ->property('tags', Schema::array(Schema::string(), 'Get the blog tags')->min(3)->max(6))
            //...
            ->output('get_blog_data')
            ->responseFormat();
    }

    /** {@inheritDoc} */
    public function messages(): array
    {
        $template = <<<MD
            Write an SEO-friendly breach blog post:
            - Start with a headline
            - Provide a short TL;DR
            - Add sections with plain-language explanations
            - Include FAQs at the end
        MD;

        return [
            ['role' => 'system', 'content' => $this->instructions()],
            ['role' => 'developer', 'content' => $template],
            ['role' => 'user',   'content' => 'Generate blog from the given breach data.'],
            ['role' => 'user',   'content' => 'Here is the breach:' . $this->prompt()],
        ];
    }


    /**
     * Hook the onComplete
     */
    protected function onComplete(CreateResponse $response): void
    {
        //custom logic
    }
}
