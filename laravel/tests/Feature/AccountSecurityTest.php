<?php

declare(strict_types=1);

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('will not move the email without the current password', function () {
    $user = User::factory()->create(['email' => 'parent@example.com', 'password' => 'the-old-one']);
    $this->actingAs($user, 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['email' => 'someone@else.com'])
        ->assertJsonValidationErrorFor('current_password');

    $this->patchJson('/api/v1/auth/me', [
        'email' => 'someone@else.com',
        'current_password' => 'the-old-one',
    ])->assertOk();

    expect($user->fresh()->email)->toBe('someone@else.com');
});

it('changes the password when the old one is given', function () {
    $user = User::factory()->create(['password' => 'the-old-one']);
    $this->actingAs($user, 'sanctum');

    $this->patchJson('/api/v1/auth/me', [
        'password' => 'a-much-better-one',
        'password_confirmation' => 'a-much-better-one',
    ])->assertJsonValidationErrorFor('current_password');

    $this->patchJson('/api/v1/auth/me', [
        'password' => 'a-much-better-one',
        'password_confirmation' => 'a-much-better-one',
        'current_password' => 'the-old-one',
    ])->assertOk();

    expect(Hash::check('a-much-better-one', $user->fresh()->password))->toBeTrue();
});

it('asks a guest for nothing, since there is no password to prove', function () {
    $user = User::factory()->create(['email' => null, 'password' => null]);
    $this->actingAs($user, 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['name' => 'Named at last'])->assertOk();
});

it('stops talking to the phone when the account closes', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/v1/devices', [
        'push_token' => 'ExponentPushToken[xxx]',
        'platform' => 'ios',
    ])->assertCreated();

    $this->deleteJson('/api/v1/auth/me')->assertOk();

    expect(Device::where('user_id', $user->id)->count())->toBe(0);
});
