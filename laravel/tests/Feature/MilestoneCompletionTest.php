<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Models\AppSetting;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Support\Limits;

/** Fill every visible milestone in a chapter, bypassing the daily cap. */
function fillChapter(Child $child, ChildChapter $chapter): void
{
    foreach ($chapter->milestones()->visible()->get() as $milestone) {
        $child->entries()->create([
            'child_milestone_id' => $milestone->id,
            'date' => now()->toDateString(),
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }
}

it('offers completion only once every visible milestone has a memory', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    expect(app(Limits::class)->canCompleteMilestone($chapter))->toBeFalse();

    fillChapter($child, $chapter);

    expect(app(Limits::class)->canCompleteMilestone($chapter->fresh()))->toBeTrue();
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
    // the First Chapter trophy, which pays its own 200.
    expect($chapter->completed_at)->not->toBeNull()
        ->and($chapter->completed_by_user_id)->toBe($user->id)
        ->and($child->fresh()->xp)->toBe($before + $chapter->xp + 200);
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

it('hides a chapter together with its milestones', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/hide", ['hidden' => true])
        ->assertOk();

    expect($chapter->fresh()->is_hidden)->toBeTrue()
        ->and($chapter->milestones()->where('is_hidden', false)->count())->toBe(0);

    $this->getJson("/api/v1/children/{$child->id}/chapters")->assertJsonCount(7, 'data');
});
