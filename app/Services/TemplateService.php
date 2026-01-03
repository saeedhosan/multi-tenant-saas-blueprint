<?php

declare(strict_types=1);

namespace App\Services;

class TemplateService
{
    /**
     * @param  array<string, string>  $data
     */
    public function __construct(private readonly array $data = []) {}

    /**
     * Render template {{key}} content by providing key and data.
     *
     * @param  array<string, string>  $data
     */
    public static function toRender(string $content = '', array $data = []): string
    {
        return (new self($data))->render($content);
    }

    /**
     * @return array<string, string>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Render text by replacing placeholders with actual values.
     */
    public function render(string $content = ''): string
    {
        $data = $this->data;

        return preg_replace_callback(
            '/{{\s*(\w+)\s*}}/',
            function (array $matches) use ($data): string {
                $key = $matches[1];

                return (string) ($data[$key] ?? "{{{$key}}}");
            },
            $content
        ) ?? '';
    }
}
