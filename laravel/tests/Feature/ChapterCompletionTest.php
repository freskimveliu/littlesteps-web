<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\Mood;
use App\Models\AppSetting;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\Trophy;
use App\Support\Limits;

/** Fill every visible milestone in a chapter, bypassing the daily cap. */
function fillChapter(Child $child, ChildChapter $chapter): void
{
    foreach ($chapter->milestones()->visible()->get() as $milestone) {
        $child->entries()->create([
            'child_milestone_id' => $milestone->id,
            'date' => now()->toDateString(),
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }
}

it('offers completion only once every visible milestone has a memory', function () {
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

it('refuses completion while a milestone is still empty', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('chapter');
});

it('will not let a parent hide a chapter down to a handful of milestones and collect the gift', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    // Leave two visible, fill them both — under min_milestones_to_complete_chapter.
    $keep = $chapter->milestones()->visible()->take(2)->pluck('id');
    $chapter->milestones()->whereNotIn('id', $keep)->update(['is_hidden' => true]);
    fillChapter($child, $chapter->fresh());

    expect($chapter->fresh()->milestones()->visible()->count())->toBe(2);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('chapter');
});

it('respects a retuned minimum', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    $keep = $chapter->milestones()->visible()->take(2)->pluck('id');
    $chapter->milestones()->whereNotIn('id', $keep)->update(['is_hidden' => true]);
    fillChapter($child, $chapter->fresh());

    AppSetting::where('key', AppSettingKey::MinMilestonesToCompleteChapter->value)->update(['value' => '2']);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();
});

it('refuses to complete the same chapter twice', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    fillChapter($child, $chapter);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();
    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('chapter');
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

    expect($chapter->abilities())->toMatchArray([
        'rename' => false,
        'delete' => false,
        'complete' => false,
        'addMilestone' => false,
        'viewRecap' => true,
    ]);
});

it('leaves a finished chapter reorderable among its siblings', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());

    expect($chapter->abilities()['reorder'])->toBeTrue();
});

it('seals the milestones inside a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());

    $this->getJson("/api/v1/children/{$child->id}/chapters")
        ->assertOk()
        ->assertJsonPath('data.0.milestones.0.abilities.rename', false)
        ->assertJsonPath('data.0.milestones.0.abilities.move', false)
        ->assertJsonPath('data.0.milestones.0.abilities.reorder', false)
        ->assertJsonPath('data.0.milestones.0.abilities.delete', false)
        ->assertJsonPath('data.0.milestones.0.abilities.skip', false)
        ->assertJsonPath('data.0.milestones.0.abilities.unskip', false);
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

it('refuses to skip a milestone inside a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $milestone = $chapter->milestones()->first();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}/hide")
        ->assertForbidden();
});

it('refuses to delete a milestone inside a finished chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $milestone = $chapter->milestones()->first();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}")
        ->assertForbidden();
});

it('still lets the memories inside a finished chapter be edited', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = finishChapter($child, $child->chapters()->first());
    $entry = $chapter->milestones()->first()->entry;

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry->id}", [
        'description' => 'Remembered it slightly differently.',
    ])->assertOk();

    expect($entry->fresh()->description)->toBe('Remembered it slightly differently.');
});
