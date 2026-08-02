<?php

declare(strict_types=1);

use App\Models\User;

it('creates a user from first launch, before anybody has typed an email', function () {
    $response = $this->postJson('/api/v1/auth/guest', [
        'name' => 'Freskim',
        'language' => 'en',
        'timezone' => 'Europe/Tirane',
    ])->assertCreated();

    expect($response->json('data.token'))->toBeString()
        ->and($response->json('data.user.isRegistered'))->toBeFalse()
        ->and(User::count())->toBe(1);
});

it('upgrades the same user on register, so nothing recorded is lost', function () {
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

// Closing an account, and coming back to it, live in AccountDeletionTest.

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
