<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Models\AppSetting;
use App\Models\Child;
use App\Models\ChildMilestone;

beforeEach(fn () => seedCatalogue());

/** Fill every visible step in a chapter, bypassing the daily cap. */
function fillChapter(Child $child, ChildMilestone $chapter): void
{
    foreach ($chapter->steps()->visible()->get() as $step) {
        $child->entries()->create([
            'child_step_id' => $step->id,
            'date' => now()->toDateString(),
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }
}

it('offers completion only once every visible step has a memory', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();

    expect(app(App\Support\Limits::class)->canCompleteMilestone($chapter))->toBeFalse();

    fillChapter($child, $chapter);

    expect(app(App\Support\Limits::class)->canCompleteMilestone($chapter->fresh()))->toBeTrue();
});

it('awards the chapter xp and stamps who finished it', function () {
    [$user, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();
    fillChapter($child, $chapter);

    $before = $child->fresh()->xp;

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")
        ->assertOk()
        ->assertJsonPath('data.xpEarned', $chapter->xp)
        ->assertJsonPath('data.milestone.isCompleted', true);

    $chapter->refresh();

    // More than the chapter's own XP: finishing the first chapter also earns
    // the First Chapter badge, which pays its own 200.
    expect($chapter->completed_at)->not->toBeNull()
        ->and($chapter->completed_by_user_id)->toBe($user->id)
        ->and($child->fresh()->xp)->toBe($before + $chapter->xp + 200);
});

it('refuses completion while a step is still empty', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('milestone');
});

it('will not let a parent hide a chapter down to a handful of steps and collect the gift', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();

    // Leave two visible, fill them both — under min_steps_to_complete_milestone.
    $keep = $chapter->steps()->visible()->take(2)->pluck('id');
    $chapter->steps()->whereNotIn('id', $keep)->update(['is_hidden' => true]);
    fillChapter($child, $chapter->fresh());

    expect($chapter->fresh()->steps()->visible()->count())->toBe(2);

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('milestone');
});

it('respects a retuned minimum', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();

    $keep = $chapter->steps()->visible()->take(2)->pluck('id');
    $chapter->steps()->whereNotIn('id', $keep)->update(['is_hidden' => true]);
    fillChapter($child, $chapter->fresh());

    AppSetting::where('key', AppSettingKey::MinStepsToCompleteMilestone->value)->update(['value' => '2']);

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")->assertOk();
});

it('refuses to complete the same chapter twice', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();
    fillChapter($child, $chapter);

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")->assertOk();
    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")
        ->assertJsonValidationErrorFor('milestone');
});

it('hides a chapter together with its steps', function () {
    [, $child] = family();
    $chapter = $child->milestones()->first();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/hide", ['hidden' => true])
        ->assertOk();

    expect($chapter->fresh()->is_hidden)->toBeTrue()
        ->and($chapter->steps()->where('is_hidden', false)->count())->toBe(0);

    $this->getJson("/api/v1/children/{$child->id}/milestones")->assertJsonCount(7, 'data');
});
