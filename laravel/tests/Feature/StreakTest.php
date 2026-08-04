<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Testing\TestResponse;

/** The first milestone the child is already old enough for. */
function openMilestone(Child $child): ChildMilestone
{
    return $child->milestones()
        ->with('chapter')
        ->orderBy('sort_order')
        ->get()
        ->firstOrFail(fn (ChildMilestone $m) => ! $m->isLockedFor($child));
}

function aMemory(Child $child, array $overrides = []): TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
        ...$overrides,
    ]);
}

it('starts a streak at one on the first memory', function () {
    [$user, $child] = family();

    aMemory($child)->assertCreated();

    expect($user->fresh()->current_streak)->toBe(1)
        ->and($user->fresh()->longest_streak)->toBe(1)
        ->and($user->fresh()->last_entry_date->toDateString())->toBe(now($user->timezone)->toDateString());
});

it('counts a day once however much is recorded in it', function () {
    [$user, $child] = family(ageMonths: 12);

    aMemory($child)->assertCreated();
    aMemory($child, ['child_milestone_id' => openMilestone($child)->id])->assertCreated();

    expect($user->fresh()->current_streak)->toBe(1);
});

it('carries the streak on when the parent comes back the next day', function () {
    [$user, $child] = family();

    aMemory($child)->assertCreated();

    $this->travel(1)->days();
    aMemory($child)->assertCreated();

    $this->travel(1)->days();
    aMemory($child)->assertCreated();

    expect($user->fresh()->current_streak)->toBe(3)
        ->and($user->fresh()->longest_streak)->toBe(3);
});

it('starts again from one after a missed day, but remembers the best run', function () {
    [$user, $child] = family();

    aMemory($child)->assertCreated();
    $this->travel(1)->days();
    aMemory($child)->assertCreated();

    // Nothing on the third day.
    $this->travel(2)->days();
    aMemory($child)->assertCreated();

    expect($user->fresh()->current_streak)->toBe(1)
        ->and($user->fresh()->longest_streak)->toBe(2);
});

it('does not let an old photo manufacture a streak', function () {
    [$user, $child] = family();

    aMemory($child, ['date' => now()->subDays(30)->toDateString()])->assertCreated();

    expect($user->fresh()->current_streak)->toBe(1)
        ->and($user->fresh()->last_entry_date->toDateString())->toBe(now($user->timezone)->toDateString());
});

it('follows the parent who wrote it, not the child', function () {
    [$user, $child] = family();
    $second = editor($child);

    $this->actingAs($second, 'sanctum');
    aMemory($child, ['child_milestone_id' => openMilestone($child)->id])->assertCreated();

    expect($second->fresh()->current_streak)->toBe(1)
        ->and($user->fresh()->current_streak)->toBe(0);
});
