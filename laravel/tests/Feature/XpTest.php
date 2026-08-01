<?php

declare(strict_types=1);

use App\Actions\Progress\AwardXp;
use App\Enums\AppSettingKey;
use App\Enums\Mood;
use App\Models\Child;
use App\Support\Progress\LevelLadder;

/** A chapter of the parent's own, filled to the brim and ready to finish. */
function finishableOwnChapter(Child $child, int $milestones): int
{
    $chapter = test()->postJson("/api/v1/children/{$child->id}/chapters", ['name' => 'Our summer'])
        ->assertCreated()
        ->json('data.id');

    foreach (range(1, $milestones) as $i) {
        $id = test()->postJson("/api/v1/children/{$child->id}/milestones", [
            'child_chapter_id' => $chapter,
            'name' => "Ours, number {$i}",
        ])->assertCreated()->json('data.id');

        // Straight into the table: the point here is the chapter's xp, not the
        // daily cap that recording them properly would run into.
        $child->entries()->create([
            'child_milestone_id' => $id,
            'date' => now()->toDateString(),
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }

    return $chapter;
}

it('starts every child at the bottom of the ladder', function () {
    $level = LevelLadder::for(0);

    expect($level['level'])->toBe(1)
        ->and($level['min_xp'])->toBe(0)
        ->and($level['next']['level'])->toBe(2)
        ->and($level['xp_to_next'])->toBe($level['next']['min_xp'])
        ->and($level['progress'])->toBe(0.0);
});

it('holds a level until the next one is actually reached', function () {
    $next = LevelLadder::for(0)['next']['min_xp'];

    expect(LevelLadder::for($next - 1)['level'])->toBe(1)
        ->and(LevelLadder::for($next - 1)['xp_to_next'])->toBe(1)
        ->and(LevelLadder::for($next)['level'])->toBe(2)
        ->and(LevelLadder::for($next)['min_xp'])->toBe($next)
        ->and(LevelLadder::for($next)['progress'])->toBe(0.0);
});

it('reports how far into a level the child has come', function () {
    $second = LevelLadder::for(0)['next']['min_xp'];
    $third = LevelLadder::for($second)['next']['min_xp'];

    $halfway = $second + intdiv($third - $second, 2);

    expect(LevelLadder::for($halfway)['progress'])->toBe(0.5)
        ->and(LevelLadder::for($halfway)['xp_to_next'])->toBe($third - $halfway);
});

it('has nowhere left to climb at the top of the ladder', function () {
    $top = LevelLadder::for(1_000_000);

    expect($top['level'])->toBe(LevelLadder::total())
        ->and($top['next'])->toBeNull()
        ->and($top['xp_to_next'])->toBeNull()
        ->and($top['progress'])->toBe(1.0);
});

it('never takes xp away', function () {
    [, $child] = family();
    $child->increment('xp', 100);

    app(AwardXp::class)->handle($child, 0);
    app(AwardXp::class)->handle($child, -50);

    expect($child->fresh()->xp)->toBe(100);
});

it('moves the child up a level as the xp crosses the line', function () {
    [, $child] = family();
    $threshold = LevelLadder::for(0)['next']['min_xp'];

    $child->increment('xp', $threshold - 10);

    $this->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.level.level', 1);

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])->assertCreated()->assertJsonPath('data.xpEarned', 10);

    // Not the exact total: a trophy unlocking alongside would pay its own on
    // top, and the level is what this is about.
    $this->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.level.level', 2);

    expect($child->fresh()->xp)->toBeGreaterThanOrEqual($threshold);
});

it('pays a milestone the parent wrote the same twenty xp as the map says', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'First trip to the sea',
    ])->assertCreated();

    $before = $child->fresh()->xp;

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->json('data.id'),
        'description' => 'She put her feet in.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])
        ->assertCreated()
        ->assertJsonPath('data.xpEarned', $milestone->json('data.xp'));

    expect($child->fresh()->xp)->toBe($before + 20);
});

it('pays nothing for finishing a chapter the parent wrote themselves', function () {
    [, $child] = family();
    $chapter = finishableOwnChapter($child, AppSettingKey::MinMilestonesToCompleteChapter->default());

    $before = $child->fresh()->xp;

    $response = $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter}/complete")
        ->assertOk()
        ->assertJsonPath('data.xpEarned', 0);

    // Whatever the child gained came from a trophy, never from the chapter —
    // otherwise a parent could write chapters until they had every level.
    $fromTrophies = collect($response->json('data.unlocked'))->sum('xp');

    expect($child->fresh()->xp - $before)->toBe($fromTrophies);
});
