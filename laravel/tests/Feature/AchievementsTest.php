<?php

declare(strict_types=1);

use App\Enums\RewardStatus;
use App\Models\Child;
use App\Models\TemplateAchievement;
use App\Support\Progress\Metrics;

beforeEach(fn () => seedCatalogue());

/** Write memories straight to the table, so the daily cap does not get in the way. */
function memoriesOn(Child $child, array $dates): void
{
    foreach ($dates as $date) {
        $child->entries()->create([
            'date' => $date,
            'description' => 'A day.',
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }
}

it('counts days, not entries', function () {
    [, $child] = family();
    $dates = collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString());

    memoriesOn($child, $dates->all());

    expect(app(Metrics::class)->days($child))->toBe(7);
});

it('counts distinct calendar months', function () {
    [, $child] = family(ageMonths: 24);

    // Fixed dates on purpose: subMonths() from a 31st overflows into the next
    // month, which would quietly collapse two of these into one.
    memoriesOn($child, ['2026-04-02', '2026-04-19', '2026-05-01', '2026-06-30']);

    expect(app(Metrics::class)->months($child))->toBe(3);
});

it('breaks the streak on a missed day', function () {
    [, $child] = family();

    memoriesOn($child, [
        now()->toDateString(),
        now()->subDay()->toDateString(),
        now()->subDays(3)->toDateString(),
    ]);

    expect(app(Metrics::class)->streak($child))->toBe(2);
});

it('only counts a step as on time when it was caught at the right age', function () {
    [, $child] = family(ageMonths: 12);
    $step = $child->steps()->where('name', 'Month 1')->first();

    // Recorded at eleven months old: a memory, but not an on-time one.
    $entry = $child->entries()->create([
        'child_step_id' => $step->id,
        'date' => $child->birthday->copy()->addMonths(11)->toDateString(),
        'created_by_user_id' => $child->created_by_user_id,
    ]);

    expect(app(Metrics::class)->onTimeSteps($child))->toBe(0);

    $entry->update(['date' => $child->birthday->copy()->addMonths(1)->addDays(3)->toDateString()]);

    expect(app(Metrics::class)->onTimeSteps($child->fresh()))->toBe(1);
});

it('unlocks a badge and awards its xp once the rule passes', function () {
    [, $child] = family();
    $badge = TemplateAchievement::where('slug', 'first-week')->first();

    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    $unlocked = app(App\Actions\Progress\EvaluateAchievements::class)->handle($child);

    expect($unlocked->pluck('template_achievement_id'))->toContain($badge->id)
        ->and($child->fresh()->xp)->toBeGreaterThanOrEqual($badge->xp);
});

it('never awards the same badge twice', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(App\Actions\Progress\EvaluateAchievements::class)->handle($child);
    $second = app(App\Actions\Progress\EvaluateAchievements::class)->handle($child->fresh());

    expect($second)->toBeEmpty()
        ->and($child->achievements()->where('template_achievement_id', TemplateAchievement::where('slug', 'first-week')->value('id'))->count())
        ->toBe(1);
});

it('keeps a badge even after the memories behind it are gone', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(App\Actions\Progress\EvaluateAchievements::class)->handle($child);
    $held = $child->achievements()->count();

    $child->entries()->delete();

    expect($child->fresh()->achievements()->count())->toBe($held);
});

it('reserves a gift unclaimed rather than generating it', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();

    foreach ($chapter->steps()->visible()->get() as $step) {
        $child->entries()->create([
            'child_step_id' => $step->id,
            'date' => now()->toDateString(),
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")->assertOk();

    $reward = $child->rewards()->first();

    expect($reward)->not->toBeNull()
        ->and($reward->status)->toBe(RewardStatus::Unclaimed)
        ->and($reward->content)->toBeNull()
        ->and($reward->generated_at)->toBeNull();
});

it('starts a generation only when the parent claims it', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->milestones()->first();

    foreach ($chapter->steps()->visible()->get() as $step) {
        $child->entries()->create([
            'child_step_id' => $step->id, 'date' => now()->toDateString(),
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->id}/complete")->assertOk();
    $reward = $child->rewards()->first();

    $this->postJson("/api/v1/children/{$child->id}/rewards/{$reward->id}/claim")
        ->assertOk()
        ->assertJsonPath('data.status', RewardStatus::Generating->value);

    $this->postJson("/api/v1/children/{$child->id}/rewards/{$reward->id}/claim")->assertStatus(409);
});

it('reports progress toward every badge', function () {
    [, $child] = family();
    memoriesOn($child, [now()->toDateString(), now()->subDay()->toDateString()]);

    $response = $this->getJson("/api/v1/children/{$child->id}/progress")->assertOk();

    $firstWeek = collect($response->json('data.badges'))->firstWhere('slug', 'first-week');

    expect($firstWeek['progress'])->toBe(2)
        ->and($firstWeek['threshold'])->toBe(7)
        ->and($firstWeek['isUnlocked'])->toBeFalse()
        ->and($response->json('data.badgesTotal'))->toBe(32);
});
