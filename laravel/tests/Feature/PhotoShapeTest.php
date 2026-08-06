<?php

declare(strict_types=1);

use App\Models\ChildEntry;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('tells the app what shape a memory photo is', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('pram.jpg', 1200, 800)]])
        ->assertCreated()
        ->assertJsonPath('data.entry.media.0.width', 1200)
        ->assertJsonPath('data.entry.media.0.height', 800);
});

it('hands the app every size a photo comes in', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('pram.jpg', 1200, 800)]])
        ->assertCreated()
        ->assertJsonStructure([
            'data' => ['entry' => ['media' => [['url', 'thumb', 'width', 'height']]]],
        ]);
});

it('never keeps the photo that arrived, only the copy it made', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('camera.jpg', 4000, 3000)]])->assertCreated();

    $stored = ChildEntry::first()->getFirstMedia(ChildEntry::MEDIA);

    [$width, $height] = getimagesize($stored->getPath());

    expect([$width, $height])->toBe([1200, 900]);
});

it('measures the copy it kept, not the one that arrived', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('camera.jpg', 4000, 3000)]])
        ->assertCreated()
        ->assertJsonPath('data.entry.media.0.width', 1200)
        ->assertJsonPath('data.entry.media.0.height', 900);
});

it('measures a photo added to a memory that already existed', function () {
    Storage::fake('public');
    [, $child] = family();

    $entryId = memory($child)->assertCreated()->json('data.entry.id');

    $this->post("/api/v1/children/{$child->id}/entries/{$entryId}", [
        '_method' => 'PATCH',
        'media' => [UploadedFile::fake()->image('later.jpg', 900, 1200)],
    ])
        ->assertOk()
        ->assertJsonPath('data.media.0.width', 900)
        ->assertJsonPath('data.media.0.height', 1200);
});

it('turns away a photo bigger than we are willing to take', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->create('camera.jpg', 21 * 1024, 'image/jpeg')]])
        ->assertStatus(422)
        ->assertJsonValidationErrors('media.0');
});

it('turns away a photo in a format nothing here can open', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->create('camera.heic', 64, 'image/heic')]])
        ->assertStatus(422)
        ->assertJsonValidationErrors('media.0');
});

it('measures the photos that were stored before anything measured them', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('before.jpg', 640, 480)]])->assertCreated();

    $photo = ChildEntry::first()->getFirstMedia(ChildEntry::MEDIA);
    $photo->forgetCustomProperty('width');
    $photo->forgetCustomProperty('height');
    $photo->save();

    $this->artisan('media:backfill')->assertSuccessful();

    expect($photo->fresh()->getCustomProperty('width'))->toBe(640)
        ->and($photo->fresh()->getCustomProperty('height'))->toBe(480);
});
