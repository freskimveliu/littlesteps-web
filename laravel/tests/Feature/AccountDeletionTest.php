<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\User;

it('closes an account without destroying anything under it', function () {
    [$user, $child] = family();

    $this->deleteJson('/api/v1/auth/me')->assertOk();

    // find() bypasses nothing — the global scope is what hides it here.
    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull()
        ->and(Child::find($child->id))->not->toBeNull()
        ->and($user->tokens()->count())->toBe(0);
});

it('reopens the account when the parent signs back in', function () {
    [$user, $child] = family();

    $this->deleteJson('/api/v1/auth/me')->assertOk();

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonPath('data.user.id', $user->id);

    expect(User::find($user->id))->not->toBeNull()
        ->and(Child::find($child->id))->not->toBeNull();
});

it('has no wrong day to come back on', function () {
    [$user, $child] = family();

    $this->deleteJson('/api/v1/auth/me')->assertOk();

    $this->travel(2)->years();

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk();

    expect(User::find($user->id))->not->toBeNull()
        ->and(Child::find($child->id))->not->toBeNull();
});

it('hands back a working token on the way in, since the old ones were revoked', function () {
    [$user] = family();

    $this->deleteJson('/api/v1/auth/me')->assertOk();
    expect($user->tokens()->count())->toBe(0);

    $token = $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->json('data.token');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id);
});

it('will not reopen an account on the wrong password', function () {
    [$user] = family();

    $this->deleteJson('/api/v1/auth/me')->assertOk();

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'not-it'])
        ->assertJsonValidationErrorFor('email');

    expect(User::find($user->id))->toBeNull()
        ->and(User::withTrashed()->find($user->id))->not->toBeNull();
});
