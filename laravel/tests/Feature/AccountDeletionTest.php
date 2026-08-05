<?php

declare(strict_types=1);

use App\Jobs\PurgeUserAccount;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/** The queue is sync under test, so the purge has run by the time this returns. */
function closeAccount(User $user): TestResponse
{
    return test()->deleteJson('/api/v1/auth/me', ['confirm' => $user->deletionPhrase()]);
}

it('will not delete an account on a mis-tap', function () {
    [$user] = family();

    $this->deleteJson('/api/v1/auth/me')->assertJsonValidationErrorFor('confirm');
    $this->deleteJson('/api/v1/auth/me', ['confirm' => 'not my name'])
        ->assertJsonValidationErrorFor('confirm');

    expect(User::find($user->id))->not->toBeNull();
});

it('takes an account with no name at its share code, since something has to be typed', function () {
    $user = User::factory()->create(['name' => '', 'email' => null, 'password' => null]);
    $this->actingAs($user, 'sanctum');

    closeAccount($user)->assertOk();

    expect(User::withTrashed()->find($user->id))->toBeNull();
});

it('cuts the account off before the emptying starts', function () {
    Queue::fake();

    [$user] = family();
    $user->devices()->create(['push_token' => 'expo-token', 'platform' => 'ios']);
    $user->createToken('phone');

    closeAccount($user)->assertOk();

    expect($user->tokens()->count())->toBe(0)
        ->and($user->devices()->count())->toBe(0);

    Queue::assertPushed(PurgeUserAccount::class);
});

it('leaves nothing of the account behind, not even a soft-deleted row', function () {
    [$user, $child] = family();

    memory($child)->assertCreated();

    closeAccount($user)->assertOk();

    expect(User::withTrashed()->find($user->id))->toBeNull()
        ->and(Child::find($child->id))->toBeNull()
        ->and(ChildEntry::where('child_id', $child->id)->count())->toBe(0);
});

it('will not let a deleted account be signed back into', function () {
    [$user] = family();

    closeAccount($user)->assertOk();

    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])
        ->assertJsonValidationErrorFor('email');
});

it('takes the photos off the disk with it', function () {
    Storage::fake('public');

    [$user, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('first-smile.jpg')]])->assertCreated();

    expect(Storage::disk('public')->allFiles("users/{$user->id}"))->not->toBeEmpty();

    closeAccount($user)->assertOk();

    expect(Storage::disk('public')->allFiles("users/{$user->id}"))->toBeEmpty();
});

it('leaves a child that was only ever shared with them where it is', function () {
    [$owner, $child] = family();

    $helper = editor($child);
    $this->actingAs($helper, 'sanctum');

    closeAccount($helper)->assertOk();

    expect(Child::find($child->id))->not->toBeNull()
        ->and($child->memberships()->where('user_id', $helper->id)->count())->toBe(0)
        ->and(User::find($owner->id))->not->toBeNull();
});

/**
 * The rule RemoveMember already draws: what you wrote stays in the adventure you
 * wrote it in. Deleting an account is leaving every adventure at once, and the
 * photos have to move to that family's folder for the memory to keep showing.
 */
it("leaves the memories they added to somebody else's child, photos and all", function () {
    Storage::fake('public');

    [$owner, $child] = family();

    $helper = editor($child);
    $this->actingAs($helper, 'sanctum');

    $entryId = memory($child, ['media' => [UploadedFile::fake()->image('park.jpg')]])
        ->assertCreated()
        ->json('data.entry.id');

    closeAccount($helper)->assertOk();

    $entry = ChildEntry::with('media')->find($entryId);

    expect($entry)->not->toBeNull()
        ->and($entry->created_by_user_id)->toBeNull()
        ->and($entry->mediaCount())->toBe(1);

    $file = $entry->getFirstMedia(ChildEntry::MEDIA);

    expect(Storage::disk('public')->exists($file->getPathRelativeToRoot()))->toBeTrue()
        ->and(Storage::disk('public')->allFiles("users/{$helper->id}"))->toBeEmpty()
        ->and(Storage::disk('public')->allFiles("users/{$owner->id}"))->not->toBeEmpty();
});
