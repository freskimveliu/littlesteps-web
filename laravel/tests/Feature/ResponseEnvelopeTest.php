<?php

declare(strict_types=1);

use App\Models\Prompt;
use App\Models\User;

/**
 * One shape in and out. The app should never have to work out which kind of body
 * it is holding before it can read the message.
 */
it('answers a success in the envelope', function () {
    [, $child] = family();

    $this->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonStructure(['data', 'code']);
});

it('answers a failure in the same envelope', function () {
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/chapters", [])
        ->assertStatus(422)
        ->assertJsonStructure(['data', 'message', 'errors', 'code'])
        ->assertJsonPath('data', null)
        ->assertJsonPath('code', 422);

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertForbidden()
        ->assertJsonPath('data', null)
        ->assertJsonPath('code', 403);
});

it('keeps data present even when there is nothing to say', function () {
    [, $child] = family();

    Prompt::query()->delete();

    $this->getJson("/api/v1/children/{$child->id}/prompt")
        ->assertOk()
        ->assertJsonStructure(['data', 'code'])
        ->assertJsonPath('data', null);
});

it('does not name the model it could not find', function () {
    family();

    $response = $this->getJson('/api/v1/children/999999')->assertNotFound();

    expect($response->json('message'))->toBe('We could not find that.')
        ->and($response->json('message'))->not->toContain('App\\Models');
});
