<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\ChildEntry;
use App\Models\User;
use App\Support\SmallerOriginal;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/** @return array{0: int, 1: int} */
function dimensions(string $path): array
{
    [$width, $height] = getimagesize($path);

    return [$width, $height];
}

it('keeps no more of a memory photo than the largest copy could want', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'date' => now()->toDateString(),
        'mood' => 'joyful',
        'description' => 'a big one',
        'media' => [UploadedFile::fake()->image('camera.jpg', 4000, 3000)],
    ])->assertCreated();

    $media = ChildEntry::first()->getFirstMedia(ChildEntry::MEDIA);

    expect(dimensions($media->getPath()))->toBe([2000, 1500]);
});

it('shrinks a child photo on the way in', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg', 3200, 3200),
    ])->assertOk();

    expect(dimensions($child->fresh()->getFirstMedia(Child::PHOTO)->getPath()))->toBe([2000, 2000]);
});

it('shrinks the photo on a profile too', function () {
    Storage::fake('public');
    [$user] = family();

    $this->postJson('/api/v1/auth/me/photo', [
        'photo' => UploadedFile::fake()->image('me.jpg', 2600, 1300),
    ])->assertOk();

    expect(dimensions($user->fresh()->getFirstMedia(User::PHOTO)->getPath()))->toBe([2000, 1000]);
});

it('leaves a photo that is already small enough at its own size', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg', 900, 600),
    ])->assertOk();

    expect(dimensions($child->fresh()->getFirstMedia(Child::PHOTO)->getPath()))->toBe([900, 600]);
});

/** GD has no libheif behind it, so touching one would mean losing it. */
it('does not lay a finger on a file it cannot read', function () {
    $heic = UploadedFile::fake()->create('camera.heic', 64, 'image/heic');
    $before = file_get_contents($heic->getRealPath());

    SmallerOriginal::of($heic);

    expect(file_get_contents($heic->getRealPath()))->toBe($before);
});
