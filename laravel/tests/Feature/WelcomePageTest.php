<?php

declare(strict_types=1);

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class WelcomePageTest extends TestCase
{
    public function test_the_welcome_page_renders_the_inertia_component(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Welcome')
                ->has('laravelVersion')
                ->has('phpVersion')
            );
    }

    public function test_the_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
