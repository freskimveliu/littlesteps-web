<?php

declare(strict_types=1);

use App\Actions\Progress\EvaluateTrophies;
use App\Enums\Mood;
use App\Enums\RewardStatus;
use App\Models\Child;
use App\Models\Trophy;
use App\Support\Progress\Metrics;

/** Write memories straight to the table, so the daily cap does not get in the way. */
function memoriesOn(Child $child, array $dates): void
{
    foreach ($dates as $date) {
        $child->entries()->create([
            'date' => $date,
            'description' => 'A day.',
            'mood' => Mood::Joyful,
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

it('only counts a milestone as on time when it was caught at the right age', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Month 1')->first();

    // Recorded at eleven months old: a memory, but not an on-time one.
    $entry = $child->entries()->create([
        'child_milestone_id' => $milestone->id,
        'date' => $child->birthday->copy()->addMonths(11)->toDateString(),
        'mood' => Mood::Joyful,
        'created_by_user_id' => $child->created_by_user_id,
    ]);

    expect(app(Metrics::class)->onTimeSteps($child))->toBe(0);

    $entry->update(['date' => $child->birthday->copy()->addMonths(1)->addDays(3)->toDateString()]);

    expect(app(Metrics::class)->onTimeSteps($child->fresh()))->toBe(1);
});

it('unlocks a trophy and awards its xp once the rule passes', function () {
    [, $child] = family();
    $trophy = Trophy::where('name', 'First Week')->first();

    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    $unlocked = app(EvaluateTrophies::class)->handle($child);

    expect($unlocked->pluck('trophy_id'))->toContain($trophy->id)
        ->and($child->fresh()->xp)->toBeGreaterThanOrEqual($trophy->xp);
});

it('never awards the same trophy twice', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(EvaluateTrophies::class)->handle($child);
    $second = app(EvaluateTrophies::class)->handle($child->fresh());

    expect($second)->toBeEmpty()
        ->and($child->trophies()->where('trophy_id', Trophy::where('name', 'First Week')->value('id'))->count())
        ->toBe(1);
});

it('keeps a trophy even after the memories behind it are gone', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(EvaluateTrophies::class)->handle($child);
    $held = $child->trophies()->count();

    $child->entries()->delete();

    expect($child->fresh()->trophies()->count())->toBe($held);
});

it('reserves a gift unclaimed rather than generating it', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    foreach ($chapter->milestones()->visible()->get() as $milestone) {
        $child->entries()->create([
            'child_milestone_id' => $milestone->id,
            'date' => now()->toDateString(),
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();

    $reward = $child->rewards()->first();

    expect($reward)->not->toBeNull()
        ->and($reward->status)->toBe(RewardStatus::Unclaimed)
        ->and($reward->content)->toBeNull()
        ->and($reward->generated_at)->toBeNull();
});

it('starts a generation only when the parent claims it', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();

    foreach ($chapter->milestones()->visible()->get() as $milestone) {
        $child->entries()->create([
            'child_milestone_id' => $milestone->id, 'date' => now()->toDateString(),
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/complete")->assertOk();
    $reward = $child->rewards()->first();

    $this->postJson("/api/v1/children/{$child->id}/rewards/{$reward->id}/claim")
        ->assertOk()
        ->assertJsonPath('data.status', RewardStatus::Generating->value);

    $this->postJson("/api/v1/children/{$child->id}/rewards/{$reward->id}/claim")->assertStatus(409);
});

it('reports progress toward every trophy', function () {
    [, $child] = family();
    memoriesOn($child, [now()->toDateString(), now()->subDay()->toDateString()]);

    $response = $this->getJson("/api/v1/children/{$child->id}/progress")->assertOk();

    $firstWeek = collect($response->json('data.trophies'))->firstWhere('name', 'First Week');

    expect($firstWeek['progress'])->toBe(2)
        ->and($firstWeek['threshold'])->toBe(7)
        ->and($firstWeek['isUnlocked'])->toBeFalse()
        ->and($response->json('data.trophiesTotal'))->toBe(32);
});

it('keeps the wording and the rule the child earned, whatever the catalogue says later', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(App\Actions\Progress\EvaluateTrophies::class)->handle($child);

    $trophy = Trophy::where('name', 'First Week')->first();
    $earned = $child->trophies()->where('trophy_id', $trophy->id)->first();
    $wasWorth = $trophy->xp;

    $trophy->update(['name' => 'Seven Whole Days', 'threshold' => 99, 'xp' => 5, 'reward' => 'book']);

    expect($earned->fresh())
        ->name->toBe('First Week')
        ->threshold->toBe(7)
        ->xp->toBe($wasWorth)
        ->reward->toBeNull();
});

it('holds on to a trophy whose catalogue row is deleted', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(App\Actions\Progress\EvaluateTrophies::class)->handle($child);

    $trophy = Trophy::where('name', 'First Week')->first();
    $earned = $child->trophies()->where('trophy_id', $trophy->id)->first();

    $trophy->forceDelete();

    expect($earned->fresh())
        ->not->toBeNull()
        ->name->toBe('First Week')
        ->trophy_id->toBeNull();
});
