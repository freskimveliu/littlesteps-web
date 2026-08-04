<?php

declare(strict_types=1);

use App\Actions\Progress\EvaluateTrophies;
use App\Enums\Icon;
use App\Enums\Mood;
use App\Enums\RewardStatus;
use App\Enums\TrophyMetric;
use App\Models\Child;
use App\Models\Trophy;
use App\Support\Progress\Metrics;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Write memories straight to the table, so the daily cap does not get in the way.
 * Each one is stamped as written on the day it is about — midday, so the parent's
 * timezone cannot tip it into a neighbouring date.
 */
function memoriesOn(Child $child, array $dates): void
{
    foreach ($dates as $date) {
        $entry = $child->entries()->create([
            'date' => $date,
            'description' => 'A day.',
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);

        $entry->forceFill(['created_at' => CarbonImmutable::parse($date)->setTime(12, 0)])->save();
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

it('counts the day a memory was written, not the day it is about', function () {
    [, $child] = family(ageMonths: 24);

    foreach (['2026-04-02', '2026-05-11', '2026-06-30', now()->subDay()->toDateString()] as $date) {
        $child->entries()->create([
            'date' => $date,
            'description' => 'Filled in one afternoon.',
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }

    expect(app(Metrics::class)->days($child))->toBe(1)
        ->and(app(Metrics::class)->months($child))->toBe(1)
        ->and(app(Metrics::class)->streak($child))->toBe(1);
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

    expect(app(Metrics::class)->onTimeMilestones($child))->toBe(0);

    $entry->update(['date' => $child->birthday->copy()->addMonths(1)->addDays(3)->toDateString()]);

    expect(app(Metrics::class)->onTimeMilestones($child->fresh()))->toBe(1);
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

    foreach ($chapter->milestones()->get() as $milestone) {
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

    foreach ($chapter->milestones()->get() as $milestone) {
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

it('unlocks a trophy that an edit finally carries over the line', function () {
    Storage::fake('public');
    [, $child] = family();

    $trophy = Trophy::create([
        'name' => 'One for the Album',
        'icon' => Icon::Camera,
        'metric' => TrophyMetric::Photos,
        'threshold' => 1,
        'xp' => 10,
        'sort_order' => 900,
        'is_active' => true,
    ]);

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])->assertCreated()->json('data.entry.id');

    expect($child->trophies()->where('trophy_id', $trophy->id)->exists())->toBeFalse();

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", [
        'media' => [UploadedFile::fake()->image('the-one.jpg')],
    ])->assertOk();

    expect($child->trophies()->where('trophy_id', $trophy->id)->exists())->toBeTrue();
});

it('counts the unlocked trophies against the catalogue it is showing', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(EvaluateTrophies::class)->handle($child);

    Trophy::where('name', 'First Week')->update(['is_active' => false]);

    $body = $this->getJson("/api/v1/children/{$child->id}/progress")->assertOk()->json('data');
    $shown = collect($body['trophies'])->where('isUnlocked', true)->count();

    expect($body['trophiesUnlocked'])->toBe($shown)
        ->and($body['trophiesUnlocked'])->toBeLessThanOrEqual($body['trophiesTotal'])
        ->and($child->trophies()->count())->toBeGreaterThan($shown);
});

it('keeps the wording and the rule the child earned, whatever the catalogue says later', function () {
    [, $child] = family();
    memoriesOn($child, collect(range(0, 6))->map(fn ($i) => now()->subDays($i)->toDateString())->all());

    app(EvaluateTrophies::class)->handle($child);

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

    app(EvaluateTrophies::class)->handle($child);

    $trophy = Trophy::where('name', 'First Week')->first();
    $earned = $child->trophies()->where('trophy_id', $trophy->id)->first();

    $trophy->forceDelete();

    expect($earned->fresh())
        ->not->toBeNull()
        ->name->toBe('First Week')
        ->trophy_id->toBeNull();
});
