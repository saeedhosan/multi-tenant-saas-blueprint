<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

class Arr extends \Illuminate\Support\Arr
{
    /**
     * Recursively transform array keys to snake case.
     *
     * @param  array<int|string, mixed>  $array
     * @param  bool  $nested
     * @return array<int|string, mixed>
     */
    public static function snakeKeys(array $array, $nested = false): array
    {
        $result = [];

        foreach ($array as $key => $value) {

            if ($nested && is_array($value)) {
                $value = self::snakeKeys($value, true);
            }

            $newKey          = is_string($key) ? Str::snake($key) : $key;
            $result[$newKey] = $value;
        }

        return $result;
    }

    /**
     * Recursively transform array keys to camel case.
     *
     * @param  array<int|string, mixed>  $array
     * @param  bool  $nested
     * @return array<int|string, mixed>
     */
    public static function camelKeys(array $array, $nested = false): array
    {
        $result = [];

        foreach ($array as $key => $value) {

            if ($nested && is_array($value)) {
                $value = self::camelKeys($value, true);
            }

            $result[\Illuminate\Support\Str::camel((string) $key)] = $value;
        }

        return $result;
    }

    /**
     * Remap the keys of an array using a given mapping array.
     *
     * @template TItems of array
     *
     * @param  TItems  $input
     * @param  array<string, string>  $map
     * @return TItems
     */
    public static function mapKeys(array $input, array $map = [], bool $refresh = true): array
    {
        if ($map === []) {
            /** @var TItems $input */
            return $input;
        }

        /** @var iterable<string, mixed> $input */
        $result = [];

        foreach ($input as $key => $value) {
            $newKey = $map[$key] ?? $key;

            if (! $refresh && isset($map[$key]) && $newKey !== $key) {
                $result[$key] = $value;
            }

            $result[$newKey] = $value;
        }

        /** @var TItems $result */
        return $result;
    }
}
