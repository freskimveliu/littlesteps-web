<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;

beforeEach(fn () => test()->withoutVite());

dataset('legal pages', [
    'terms' => ['/terms', 'Terms'],
    'privacy' => ['/privacy', 'Privacy'],
]);

it('opens to a visitor with no account', function (string $path, string $component) {
    $this->get($path)
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component($component));
})->with('legal pages');
