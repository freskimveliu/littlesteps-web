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

it('refuses a memory with neither words nor anything attached', function () {
    [, $child] = family();

    memory($child, ['description' => null])->assertJsonValidationErrorFor('description');

    expect($child->entries()->count())->toBe(0);
});

it('accepts a wordless memory when it carries an attachment', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, [
        'description' => null,
        'media' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated();

    expect($child->entries()->first()->mediaCount())->toBe(1);
});

it('attaches no more to a memory than the settings allow', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, [
        'media' => collect(range(1, app(Limits::class)->maxMediaPerEntry() + 1))
            ->map(fn (int $i) => UploadedFile::fake()->image("shot-{$i}.jpg"))
            ->all(),
    ])->assertJsonValidationErrorFor('media');

    expect($child->entries()->count())->toBe(0);
});

it('serves the media urls back when listing memories', function () {
    Storage::fake('public');
    [, $child] = family();

    memory($child, ['media' => [UploadedFile::fake()->image('first-smile.jpg')]])->assertCreated();

    $this->getJson("/api/v1/children/{$child->id}/entries")
        ->assertOk()
        ->assertJsonCount(1, 'data.items.0.media');
});

it('refuses an attachment beyond the cap added after the fact', function () {
    Storage::fake('public');
    [, $child] = family();

    $most = app(Limits::class)->maxMediaPerEntry();

    $entry = memory($child, [
        'media' => collect(range(1, $most))
            ->map(fn (int $i) => UploadedFile::fake()->image("shot-{$i}.jpg"))
            ->all(),
    ])->assertCreated()->json('data.entry.id');

    $this->postJson("/api/v1/children/{$child->id}/entries/{$entry}/media", [
        'file' => UploadedFile::fake()->image('one-too-many.jpg'),
    ])->assertJsonValidationErrorFor('file');

    expect(ChildEntry::find($entry)->mediaCount())->toBe($most);
});

it('refuses an edit that empties a memory of both words and media', function () {
    [, $child] = family();

    $entry = memory($child)->assertCreated()->json('data.entry.id');

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", ['description' => ''])
        ->assertJsonValidationErrorFor('description');

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", ['mood' => null])
        ->assertJsonValidationErrorFor('mood');

    expect(ChildEntry::find($entry)->description)->toBe('She laughed at the cat.');
});

it('allows clearing the words while an attachment still carries the memory', function () {
    Storage::fake('public');
    [, $child] = family();

    $entry = memory($child, [
        'media' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated()->json('data.entry.id');

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", ['description' => ''])
        ->assertOk();
});

it('refuses to remove the last attachment from a wordless memory', function () {
    Storage::fake('public');
    [, $child] = family();

    $entry = memory($child, [
        'description' => null,
        'media' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated()->json('data.entry.id');

    $file = ChildEntry::find($entry)->getMedia(ChildEntry::MEDIA)->first();

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}/media/{$file->id}")
        ->assertJsonValidationErrorFor('file');

    expect(ChildEntry::find($entry)->mediaCount())->toBe(1);
});
