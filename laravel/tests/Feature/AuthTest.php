<?php

declare(strict_types=1);

use App\Models\User;

it('creates a user from first launch, before anybody has typed an email', function () {
    $response = $this->postJson('/api/v1/auth/guest', [
        'name' => 'Freskim',
        'language' => 'sq',
        'timezone' => 'Europe/Tirane',
    ])->assertCreated();

    expect($response->json('data.token'))->toBeString()
        ->and($response->json('data.user.isRegistered'))->toBeFalse()
        ->and(User::count())->toBe(1);
});

it('upgrades the same user on register, so nothing recorded is lost', function () {
    seedCatalogue();
    [$user, $child] = family();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Freskim',
        'email' => 'freskim@example.com',
        'password' => 'sekret-passphrase',
        'password_confirmation' => 'sekret-passphrase',
    ])->assertCreated()->assertJsonPath('data.user.isRegistered', true);

    expect(User::count())->toBe(1)
        ->and($user->fresh()->email)->toBe('freskim@example.com')
        ->and($child->fresh()->created_by_user_id)->toBe($user->id);
});

it('logs a registered user back in', function () {
    $user = User::factory()->create(['email' => 'a@b.com']);

    $this->postJson('/api/v1/auth/login', ['email' => 'a@b.com', 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id);
});

it('refuses a login for an account that never set a password', function () {
    User::factory()->create(['email' => 'guest@b.com', 'password' => null]);

    $this->postJson('/api/v1/auth/login', ['email' => 'guest@b.com', 'password' => 'anything'])
        ->assertJsonValidationErrorFor('email');
});

it('soft deletes an account and gives it 30 days to come back', function () {
    seedCatalogue();
    [$user, $child] = family();

    $this->deleteJson('/api/v1/auth/me')->assertOk();

    // fresh() bypasses global scopes, so ask through the scoped query instead.
    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and($child->fresh())->not->toBeNull()
        ->and($user->tokens()->count())->toBe(0);
});

it('stores notification preferences as settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $this->getJson('/api/v1/auth/me')->assertJsonPath('data.settings.daily_quests', true);

    $this->patchJson('/api/v1/auth/me', ['settings' => ['daily_quests' => false]])
        ->assertOk()
        ->assertJsonPath('data.settings.daily_quests', false);
});

it('locks everything behind a token', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->getJson('/api/v1/children')->assertUnauthorized();
});
