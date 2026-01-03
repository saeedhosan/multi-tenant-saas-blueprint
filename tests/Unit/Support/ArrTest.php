<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{
    public function test_snake_keys_simple()
    {
        $input = [
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ];

        $expected = [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ];

        $this->assertEquals($expected, Arr::snakeKeys($input));
    }

    public function test_snake_keys_nested()
    {
        $input = [
            'firstName' => 'John',
            'address'   => [
                'streetName' => 'Main St',
                'postalCode' => '12345',
            ],
            'phoneNumbers' => [
                ['type' => 'home', 'number' => '123-456-7890'],
                ['type' => 'work', 'number' => '987-654-3210'],
            ],
        ];

        $expected = [
            'first_name' => 'John',
            'address'    => [
                'street_name' => 'Main St',
                'postal_code' => '12345',
            ],
            'phone_numbers' => [
                ['type' => 'home', 'number' => '123-456-7890'],
                ['type' => 'work', 'number' => '987-654-3210'],
            ],
        ];

        $this->assertEquals($expected, Arr::snakeKeys($input, true));
    }

    public function test_snake_keys_nested_false_should_not_convert_nested()
    {
        $input = [
            'firstName' => 'John',
            'address'   => [
                'streetName' => 'Main St',
                'postalCode' => '12345',
            ],
        ];

        $expected = [
            'first_name' => 'John',
            'address'    => [ // keys should remain camelCase because $nested = false
                'streetName' => 'Main St',
                'postalCode' => '12345',
            ],
        ];

        $this->assertEquals($expected, Arr::snakeKeys($input, false));
    }

    public function test_camel_keys_simple()
    {
        $input = [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ];

        $expected = [
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ];

        $this->assertEquals($expected, Arr::camelKeys($input));
    }

    public function test_camel_keys_nested()
    {
        $input = [
            'first_name' => 'John',
            'address'    => [
                'street_name' => 'Main St',
                'postal_code' => '12345',
            ],
        ];

        $expected = [
            'firstName' => 'John',
            'address'   => [
                'streetName' => 'Main St',
                'postalCode' => '12345',
            ],
        ];

        $this->assertEquals($expected, Arr::camelKeys($input, true));
    }

    public function test_camel_keys_nested_false_should_not_convert_nested()
    {
        $input = [
            'first_name' => 'John',
            'address'    => [
                'street_name' => 'Main St',
                'postal_code' => '12345',
            ],
        ];

        $expected = [
            'firstName' => 'John',
            'address'   => [ // keys remain snake_case because $nested = false
                'street_name' => 'Main St',
                'postal_code' => '12345',
            ],
        ];

        $this->assertEquals($expected, Arr::camelKeys($input, false));
    }

    public function test_empty_array()
    {
        $this->assertEquals([], Arr::snakeKeys([]));
        $this->assertEquals([], Arr::camelKeys([]));
    }

    /**
     * ==================================================================
     * Arr::mapKeys
     * ==================================================================
     */
    public function test_it_remaps_array_keys()
    {
        $input = ['first_name' => 'John', 'last_name' => 'Doe'];
        $map   = ['first_name' => 'firstName', 'last_name' => 'lastName'];

        $expected = ['firstName' => 'John', 'lastName' => 'Doe'];

        $this->assertSame($expected, Arr::mapKeys($input, $map));
    }

    public function test_it_returns_same_array_when_map_is_empty()
    {
        $input = ['id' => 1, 'name' => 'Alice'];

        $this->assertSame($input, Arr::mapKeys($input, []));
    }

    public function test_it_only_remaps_specified_keys()
    {
        $input = ['id' => 1, 'name' => 'Alice'];
        $map   = ['name' => 'full_name'];

        $expected = ['id' => 1, 'full_name' => 'Alice'];

        $this->assertSame($expected, Arr::mapKeys($input, $map));
    }

    public function test_it_keeps_both_original_and_new_keys_when_refresh_is_false()
    {
        $input = ['username' => 'jdoe', 'email' => 'john@example.com'];
        $map   = ['username' => 'user_name'];

        $expected = [
            'username'  => 'jdoe',  // original key kept
            'user_name' => 'jdoe', // new key added
            'email'     => 'john@example.com',
        ];

        $this->assertSame($expected, Arr::mapKeys($input, $map, refresh: false));
    }

    public function test_it_does_not_duplicate_when_key_is_not_renamed()
    {
        $input = ['id' => 1];
        $map   = ['id' => 'id']; // same name, no rename

        $expected = ['id' => 1];

        $this->assertSame($expected, Arr::mapKeys($input, $map, refresh: false));
    }

    public function test_it_handles_numeric_keys_correctly()
    {
        $input = [10 => 'foo', 20 => 'bar'];
        $map   = [10 => 'a', 20 => 'b'];

        $expected = ['a' => 'foo', 'b' => 'bar'];

        $this->assertSame($expected, Arr::mapKeys($input, $map));
    }
}
