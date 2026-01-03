<?php

declare(strict_types=1);

namespace Tests;

use App\Ai\LLMProvider;
use Bright\Fauth\Facades\Fauth;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        LLMProvider::fake();

        if (class_exists(Fauth::class)) {
            Fauth::fake();
        }
    }
}
