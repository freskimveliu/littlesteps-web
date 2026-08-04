<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Models\Child;
use Illuminate\Support\Facades\DB;

/** Its own, so this file can be run on its own. */
function aRecordedAfternoon(Child $child): void
{
    $child->entries()->create([
        'description' => 'The afternoon in the garden.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful,
        'created_by_user_id' => $child->created_by_user_id,
    ]);
}

/**
 * A grandparent who may only look should be handed a map with nothing to press.
 *
 * The rules live on the server precisely so the app does not have to know them;
 * a payload that says `rename: true` to somebody who will get a 403 for renaming
 * is the app finding out the hard way, in front of the parent.
 */
it('hands a read-only member no abilities at all', function () {
    [, $child] = family(ageMonths: 24);
    aRecordedAfternoon($child);

    $this->actingAs(viewer($child), 'sanctum');

    $map = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();

    expect($map->json('data.0.abilities'))->each->toBeFalse()
        ->and($map->json('data.0.milestones.0.abilities'))->each->toBeFalse();

    $entries = $this->getJson("/api/v1/children/{$child->id}/entries")->assertOk();

    expect($entries->json('data.items.0.abilities.edit'))->toBeFalse()
        ->and($entries->json('data.items.0.abilities.delete'))->toBeFalse();
});

it('still lets the parent who owns the child do those things', function () {
    [, $child] = family(ageMonths: 24);
    aRecordedAfternoon($child);

    $map = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();

    expect($map->json('data.0.abilities.rename'))->toBeTrue()
        ->and($map->json('data.0.abilities.delete'))->toBeTrue()
        ->and($map->json('data.0.milestones.0.abilities.record'))->toBeTrue();

    $entries = $this->getJson("/api/v1/children/{$child->id}/entries")->assertOk();

    expect($entries->json('data.items.0.abilities.edit'))->toBeTrue()
        ->and($entries->json('data.items.0.abilities.delete'))->toBeTrue();
});

it('says the same thing to an editor as to the parent', function () {
    [, $child] = family(ageMonths: 24);

    $this->actingAs(editor($child), 'sanctum');

    $map = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();

    expect($map->json('data.0.abilities.rename'))->toBeTrue()
        ->and($map->json('data.0.milestones.0.abilities.record'))->toBeTrue();
});

/**
 * The map is one screen. It used to ask the settings table for the same six
 * numbers thirteen times and count each chapter's milestones twice over, which
 * is what this budget exists to stop coming back.
 */
it('draws the whole map without a query per chapter', function () {
    [, $child] = family(ageMonths: 24);

    $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();

    DB::enableQueryLog();
    $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($child->chapters()->count())->toBeGreaterThan(5)
        ->and($queries)->toBeLessThan(15);
});
