<?php

declare(strict_types=1);

namespace App\Ai;

/**
 * Lightweight stand-in for an AI chat response.
 */
class CreateResponse implements \ArrayAccess
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(private array $attributes)
    {
    }

    /**
     * Factory method used by tests.
     */
    public static function fake(array $attributes): self
    {
        return new self($attributes);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists((string) $offset, $this->attributes);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[(string) $offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[(string) $offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[(string) $offset]);
    }

    public function __get(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }
}
