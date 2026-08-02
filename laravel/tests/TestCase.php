<?php

namespace Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Strictness is pinned here as well as in the service provider, so the suite
     * refuses to lazy load whatever environment it is pointed at.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Model::preventLazyLoading();
    }
}
