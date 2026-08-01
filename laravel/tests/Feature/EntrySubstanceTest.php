<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Models\Child;
use App\Models\ChildEntry;
use App\Support\Limits;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

function memory(Child $child, array $overrides = []): TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
        ...$overrides,
    ]);
}

it('refuses a memory with no mood', function () {
    [, $child] = family();

    memory($child, ['mood' => null])->assertJsonValidationErrorFor('mood');

    expect($child->entries()->count())->toBe(0);
});

it('refuses a memory with neither words nor a photo', function () {
    [, $child] = family();

    memory($child, ['description' => null])->assertJsonValidationErrorFor('description');

    expect($child->entries()->count())->toBe(0);
});

it('accepts a wordless memory when it carries a photo', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, [
        'description' => null,
        'photos' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated();

    expect($child->entries()->first()->photoCount())->toBe(1);
});

it('takes no more photos with a memory than the settings allow', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, [
        'photos' => collect(range(1, app(Limits::class)->maxMediaPerEntry() + 1))
            ->map(fn (int $i) => UploadedFile::fake()->image("shot-{$i}.jpg"))
            ->all(),
    ])->assertJsonValidationErrorFor('photos');

    expect($child->entries()->count())->toBe(0);
});

it('serves the photo urls back when listing memories', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['photos' => [UploadedFile::fake()->image('first-smile.jpg')]])->assertCreated();

    $this->getJson("/api/v1/children/{$child->id}/entries")
        ->assertOk()
        ->assertJsonCount(1, 'data.items.0.photos');
});

it('refuses a fourth photo added after the fact', function () {
    Storage::fake('public');
    [, $child] = family();

    $most = app(Limits::class)->maxMediaPerEntry();

    $entry = memory($child, [
        'photos' => collect(range(1, $most))
            ->map(fn (int $i) => UploadedFile::fake()->image("shot-{$i}.jpg"))
            ->all(),
    ])->assertCreated()->json('data.entry.id');

    $this->postJson("/api/v1/children/{$child->id}/entries/{$entry}/photos", [
        'photo' => UploadedFile::fake()->image('one-too-many.jpg'),
    ])->assertJsonValidationErrorFor('photo');

    expect(ChildEntry::find($entry)->photoCount())->toBe($most);
});

it('refuses an edit that empties a memory of both words and photos', function () {
    [, $child] = family();

    $entry = memory($child)->assertCreated()->json('data.entry.id');

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", ['description' => ''])
        ->assertJsonValidationErrorFor('description');

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", ['mood' => null])
        ->assertJsonValidationErrorFor('mood');

    expect(ChildEntry::find($entry)->description)->toBe('She laughed at the cat.');
});

it('allows clearing the words while a photo still carries the memory', function () {
    Storage::fake('public');
    [, $child] = family();

    $entry = memory($child, [
        'photos' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated()->json('data.entry.id');

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", ['description' => ''])
        ->assertOk();
});

it('refuses to remove the last photo from a wordless memory', function () {
    Storage::fake('public');
    [, $child] = family();

    $entry = memory($child, [
        'description' => null,
        'photos' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated()->json('data.entry.id');

    $photo = ChildEntry::find($entry)->getMedia(ChildEntry::PHOTOS)->first();

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}/photos/{$photo->id}")
        ->assertJsonValidationErrorFor('photo');

    expect(ChildEntry::find($entry)->photoCount())->toBe(1);
});
