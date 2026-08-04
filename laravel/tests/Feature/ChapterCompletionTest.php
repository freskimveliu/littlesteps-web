<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\Mood;
use App\Models\AppSetting;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\Trophy;
use App\Support\Limits;

/** Fill every milestone in a chapter, bypassing the daily cap. */
function fillChapter(Child $child, ChildChapter $chapter): void
{
    foreach ($chapter->milestones()->get() as $milestone) {
        $child->entries()->create([
            'child_milestone_id' => $milestone->id,
            'date' => now()->toDateString(),
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }
}

it('offers completion only once every milestone has a memory', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    expect(app(Limits::class)->canCompleteChapter($chapter))->toBeFalse();

    fillChapter($child, $chapter);

    expect(app(Limits::class)->canCompleteChapter($chapter->fresh()))->toBeTrue();
});

it('awards the chapter xp and stamps who finished it', function () {
    [$user, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    fillChapter($child, $chapter);

    $before = $child->fresh()->xp;

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.xpEarned', $chapter->xp)
        ->assertJsonPath('data.chapter.isCompleted', true);

    $chapter->refresh();

    // More than the chapter's own XP: finishing the first chapter also earns
    // the First Chapter trophy, which pays its own on top.
    $trophyXp = Trophy::where('name', 'First Chapter')->value('xp');

    expect($chapter->completed_at)->not->toBeNull()
        ->and($chapter->completed_by_user_id)->toBe($user->id)
        ->and($child->fresh()->xp)->toBe($before + $chapter->xp + $trophyXp);
});

it('counts a deleted milestone as gone, not as missing', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $minimum = AppSetting::number(AppSettingKey::MinMilestonesToCompleteChapter);

    // Take off only the slack above the minimum, so the deletion is the reason
    // this chapter can close — not a retuned limit, and not an empty map.
    $spare = $chapter->milestones()->count() - $minimum;
    expect($spare)->toBeGreaterThan(0);

    foreach ($chapter->milestones()->take($spare)->pluck('id') as $id) {
        $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$id}")->assertNoContent();
    }

    fillChapter($child, $chapter->fresh());

    expect($chapter->fresh()->milestones()->count())->toBe($minimum);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.chapter.isCompleted', true);
});

it('refuses completion while one milestone is missing its memory', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    fillChapter($child, $chapter);
    $chapter->milestones()->first()->entry()->delete();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('chapter');
});

it('refuses completion while a milestone is still empty', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('chapter');
});

it('will not let a parent delete a chapter down to a handful of milestones and collect the gift', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    // Leave two standing, fill them both — under min_milestones_to_complete_chapter.
    $keep = $chapter->milestones()->take(2)->pluck('id');
    $chapter->milestones()->whereNotIn('id', $keep)->delete();
    fillChapter($child, $chapter->fresh());

    expect($chapter->fresh()->milestones()->count())->toBe(2);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('chapter');
});

it('respects a retuned minimum', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    $keep = $chapter->milestones()->take(2)->pluck('id');
    $chapter->milestones()->whereNotIn('id', $keep)->delete();
    fillChapter($child, $chapter->fresh());

    setting(AppSettingKey::MinMilestonesToCompleteChapter, 2);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();
});

it('refuses to complete the same chapter twice', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    fillChapter($child, $chapter);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();
    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertForbidden()
        ->assertJsonPath('message', 'This chapter is already finished.');
});

/** Fill a chapter and finish it, the way a parent does. */
function finishChapter(Child $child, ChildChapter $chapter): ChildChapter
{
    fillChapter($child, $chapter);
    test()->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();

    return $chapter->fresh();
}

it('stops offering anything but the recap once a chapter is finished', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());

    expect($chapter->abilities(mayWrite: true))->toMatchArray([
        'rename' => false,
        'delete' => false,
        'complete' => false,
        'addMilestone' => false,
        'viewRecap' => true,
    ]);
});

it('leaves a finished chapter of the parents own reorderable among its siblings', function () {
    [$user, $child] = family(ageMonths: 6);

    $own = $child->chapters()->create([
        'name' => 'Our summer by the sea',
        'sort_order' => 999,
        'is_editable' => true,
        'created_by_user_id' => $user->id,
    ]);

    $own->milestones()->createMany([
        ['child_id' => $child->id, 'name' => 'The first swim', 'sort_order' => 10, 'is_editable' => true],
        ['child_id' => $child->id, 'name' => 'The long drive home', 'sort_order' => 20, 'is_editable' => true],
    ]);

    setting(AppSettingKey::MinMilestonesToCompleteChapter, 2);

    expect(finishChapter($child, $own->fresh())->abilities(mayWrite: true)['reorder'])->toBeTrue();
});

it('keeps a guided chapter in its age order, finished or not', function () {
    [, $child] = family(ageMonths: 6);
    $guided = $child->chapters()->first();

    expect($guided->abilities(mayWrite: true)['reorder'])->toBeFalse()
        ->and(finishChapter($child, $guided)->abilities(mayWrite: true)['reorder'])->toBeFalse();
});

it('seals the milestones inside a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());

    $this->getJson("/api/v1/children/{$child->id}/chapters")
        ->assertOk()
        ->assertJsonPath('data.0.milestones.0.abilities.rename', false)
        ->assertJsonPath('data.0.milestones.0.abilities.move', false)
        ->assertJsonPath('data.0.milestones.0.abilities.reorder', false)
        ->assertJsonPath('data.0.milestones.0.abilities.delete', false);
});

it('refuses to rename a chapter that has been finished', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}", ['name' => 'Renamed'])
        ->assertForbidden();

    expect($chapter->fresh()->name)->not->toBe('Renamed');
});

it('refuses to rename a milestone inside a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $milestone = $chapter->milestones()->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", ['name' => 'Renamed'])
        ->assertForbidden();
});

it('refuses to add a milestone to a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());

    $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'One more',
    ])->assertJsonValidationErrorFor('child_chapter_id');
});

it('refuses to move a milestone into a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $finished = finishChapter($child, $child->chapters()->first());
    $open = $child->chapters()->where('id', '!=', $finished->id)->first();
    $milestone = $open->milestones()->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", [
        'child_chapter_id' => $finished->id,
    ])->assertJsonValidationErrorFor('child_chapter_id');
});

it('refuses to reorder the milestones of a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $ids = $chapter->milestones()->orderBy('sort_order')->pluck('id')->reverse()->values();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/reorder", [
        'milestones' => $ids->all(),
    ])->assertForbidden();
});

it('refuses to delete a milestone inside a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $milestone = $chapter->milestones()->first();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}")
        ->assertForbidden();
});

it('refuses to empty a chapter into a finished one', function () {
    [, $child] = family(ageMonths: 6);
    $finished = finishChapter($child, $child->chapters()->first());
    $doomed = $child->chapters()->where('id', '!=', $finished->id)->first();

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$doomed->id}", [
        'move_milestones_to' => $finished->id,
    ])->assertForbidden();

    expect($doomed->fresh())->not->toBeNull()
        ->and($finished->milestones()->whereDoesntHave('entry')->count())->toBe(0);
});

it('seals the memories inside a finished chapter against editing', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $entry = $chapter->milestones()->first()->entry;
    $written = $entry->description;

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry->id}", [
        'description' => 'Remembered it slightly differently.',
    ])->assertForbidden();

    expect($entry->fresh()->description)->toBe($written);
});
