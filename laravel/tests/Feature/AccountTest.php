<?php

declare(strict_types=1);

use App\Enums\DevicePlatform;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('saves the name and the timezone the parent chose', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);
    $this->actingAs($user, 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['name' => 'Freskim', 'timezone' => 'Europe/Tirane'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Freskim')
        ->assertJsonPath('data.timezone', 'Europe/Tirane');

    expect($user->fresh()->timezone)->toBe('Europe/Tirane');
});

it('refuses a timezone no clock keeps', function () {
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['timezone' => 'Middle/Earth'])
        ->assertJsonValidationErrorFor('timezone');
});

it('refuses a language the app does not speak yet', function () {
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['language' => 'de'])
        ->assertJsonValidationErrorFor('language');
});

it('refuses an email another account already uses', function () {
    $taken = User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'mine@example.com']);
    $this->actingAs($user, 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['email' => $taken->email])
        ->assertJsonValidationErrorFor('email');

    // Keeping your own is not a clash with yourself.
    $this->patchJson('/api/v1/auth/me', ['email' => 'mine@example.com'])->assertOk();
});

it('ignores a setting it has never heard of', function () {
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->patchJson('/api/v1/auth/me', ['settings' => ['send_me_spam' => true]])
        ->assertOk()
        ->assertJsonMissingPath('data.settings.send_me_spam');
});

it('stores a photo for the parent', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $response = $this->postJson('/api/v1/auth/me/photo', [
        'photo' => UploadedFile::fake()->image('me.jpg'),
    ])->assertOk();

    expect($response->json('data.photo.url'))->toBeString()
        ->and($user->fresh()->getMedia(User::PHOTO))->toHaveCount(1);
});

it('refuses a parent photo that is not an image', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->postJson('/api/v1/auth/me/photo', [
        'photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    ])->assertJsonValidationErrorFor('photo');
});

it('registers a device for push', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/v1/devices', [
        'push_token' => 'expo-token-1',
        'platform' => DevicePlatform::Ios->value,
    ])->assertCreated();

    expect($user->devices()->count())->toBe(1);
});

it('refuses a platform that is not a phone we ship to', function () {
    $this->actingAs(User::factory()->create(), 'sanctum');

    $this->postJson('/api/v1/devices', ['push_token' => 'expo-token-1', 'platform' => 'blackberry'])
        ->assertJsonValidationErrorFor('platform');
});

it('moves a handset to whoever signed in on it last', function () {
    $first = User::factory()->create();
    $second = User::factory()->create();

    $this->actingAs($first, 'sanctum')
        ->postJson('/api/v1/devices', ['push_token' => 'expo-token-1', 'platform' => DevicePlatform::Ios->value])
        ->assertCreated();

    $this->actingAs($second, 'sanctum')
        ->postJson('/api/v1/devices', ['push_token' => 'expo-token-1', 'platform' => DevicePlatform::Ios->value])
        ->assertCreated();

    expect(Device::where('push_token', 'expo-token-1')->count())->toBe(1)
        ->and($first->devices()->count())->toBe(0)
        ->and($second->devices()->count())->toBe(1);
});

it('forgets a device on the way out', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'sanctum');

    $this->postJson('/api/v1/devices', [
        'push_token' => 'expo-token-1',
        'platform' => DevicePlatform::Android->value,
    ])->assertCreated();

    $this->deleteJson('/api/v1/devices', ['push_token' => 'expo-token-1'])->assertNoContent();

    expect($user->devices()->count())->toBe(0);
});

it('leaves another parents device alone', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $this->actingAs($owner, 'sanctum')
        ->postJson('/api/v1/devices', ['push_token' => 'expo-token-1', 'platform' => DevicePlatform::Ios->value])
        ->assertCreated();

    $this->actingAs($stranger, 'sanctum')
        ->deleteJson('/api/v1/devices', ['push_token' => 'expo-token-1'])
        ->assertNoContent();

    expect($owner->devices()->count())->toBe(1);
});
