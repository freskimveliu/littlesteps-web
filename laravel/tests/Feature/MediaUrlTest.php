<?php

declare(strict_types=1);

use App\Models\Child;
use App\Models\ChildEntry;
use App\Support\MediaUrl;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stands a private disk up in front of the media library: a fake that hands
 * out signed links, the way S3 does. Anything added while this is on lands
 * somewhere the world cannot read.
 */
function privateDisk(): void
{
    Storage::fake('s3');
    Storage::disk('s3')->buildTemporaryUrlsUsing(
        fn (string $path, DateTimeInterface $expires) => "https://signed.test/{$path}?expires={$expires->getTimestamp()}",
    );
    config(['media-library.disk_name' => 's3']);
}

it('is pointed at no real bucket', function () {
    expect(config('filesystems.disks.s3.bucket'))->toBe('testing')
        ->and(config('filesystems.disks.s3.endpoint'))->toBe('http://s3.invalid')
        ->and(config('media-library.disk_name'))->toBe('public');
});

it('hands out a route of our own for media the world cannot read', function () {
    privateDisk();
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg'),
    ])->assertOk();

    $photo = $child->fresh()->getFirstMedia(Child::PHOTO);

    expect($photo->disk)->toBe('s3')
        ->and(MediaUrl::for($photo))->toBe(route('media.show', ['uuid' => $photo->uuid]))
        ->and(MediaUrl::for($photo))->not->toContain('signed.test');
});

it('leaves media on a disk the world already reads alone', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg'),
    ])->assertOk();

    $photo = $child->fresh()->getFirstMedia(Child::PHOTO);

    expect(MediaUrl::for($photo))->toBe($photo->getUrl())
        ->and(MediaUrl::for($photo))->not->toContain('/media/');
});

it('sends a caller on to a signed link that expires', function () {
    privateDisk();
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg'),
    ])->assertOk();

    $photo = $child->fresh()->getFirstMedia(Child::PHOTO);

    $response = $this->get("/api/v1/media/{$photo->uuid}");

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toStartWith('https://signed.test/')
        ->and($response->headers->get('Cache-Control'))->toContain('max-age=3600');
});

it('asks for no token, because an image tag carries none', function () {
    privateDisk();
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg'),
    ])->assertOk();

    $photo = $child->fresh()->getFirstMedia(Child::PHOTO);

    // Nobody signed in at all, the way the phone's image loader arrives.
    auth()->forgetGuards();

    $this->get("/api/v1/media/{$photo->uuid}")->assertRedirect();
});

it('knows nothing of a uuid it never handed out', function () {
    $this->get('/api/v1/media/'.fake()->uuid())->assertNotFound();
});

/** A HEIC lands whole because nothing on the server can read it — see the
 *  conversions on ChildEntry. The link for its thumb must still work. */
it('falls back to the original when the conversion was never made', function () {
    privateDisk();
    [$user, $child] = family();

    $entry = ChildEntry::create([
        'child_id' => $child->id,
        'description' => 'no conversions here',
        'date' => now()->toDateString(),
        'mood' => 'joyful',
        'created_by_user_id' => $user->id,
        'updated_by_user_id' => $user->id,
    ]);

    $media = $entry->addMedia(UploadedFile::fake()->image('liza.jpg'))
        ->toMediaCollection(ChildEntry::MEDIA);

    DB::table('media')->where('id', $media->id)->update(['generated_conversions' => '[]']);
    $media->refresh();

    expect($media->hasGeneratedConversion('thumb'))->toBeFalse()
        ->and(MediaUrl::for($media, 'thumb'))->toBe(route('media.show', ['uuid' => $media->uuid]));

    $this->get("/api/v1/media/{$media->uuid}/thumb")
        ->assertRedirect()
        ->assertRedirectContains($media->file_name);
});

it('turns away a conversion nobody makes', function () {
    privateDisk();
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'photo' => UploadedFile::fake()->image('liza.jpg'),
    ])->assertOk();

    $photo = $child->fresh()->getFirstMedia(Child::PHOTO);

    $this->get("/api/v1/media/{$photo->uuid}/enormous")->assertNotFound();
});
