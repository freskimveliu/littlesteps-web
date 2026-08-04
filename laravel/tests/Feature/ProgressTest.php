<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\Mood;
use App\Models\Trophy;
use App\Models\User;
use Illuminate\Support\Str;

it('reports the level, the trophies and what is left of today', function () {
    [, $child] = family();

    $body = $this->getJson("/api/v1/children/{$child->id}/progress")->assertOk()->json('data');

    expect($body['xp'])->toBe(0)
        ->and($body['level']['level'])->toBe(1)
        ->and($body['trophiesUnlocked'])->toBe(0)
        ->and($body['trophiesTotal'])->toBe(Trophy::active()->count())
        ->and($body['trophies'])->toHaveCount($body['trophiesTotal'])
        ->and($body['limits']['freeEntriesLeft'])->toBe(AppSettingKey::DailyFreeEntries->default())
        ->and($body['limits']['milestoneEntriesLeft'])->toBe(AppSettingKey::DailyMilestoneEntries->default());
});

it('counts the day down as memories are recorded', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = openMilestone($child);

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])->assertCreated();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id,
        'description' => 'One for the album.',
        'date' => now()->toDateString(),
        'mood' => Mood::Proud->value,
    ])->assertCreated();

    $this->getJson("/api/v1/children/{$child->id}/progress")
        ->assertOk()
        ->assertJsonPath('data.limits.freeEntriesLeft', 0)
        ->assertJsonPath('data.limits.milestoneEntriesLeft', AppSettingKey::DailyMilestoneEntries->default() - 1);
});

it('carries every metric a trophy is judged on', function () {
    [, $child] = family();

    $metrics = $this->getJson("/api/v1/children/{$child->id}/progress")->assertOk()->json('data.metrics');

    expect($metrics)->not->toBeEmpty();

    // Spelled the app's way on the way out; the enum keeps the column's spelling.
    foreach (Trophy::active()->get() as $trophy) {
        expect($metrics)->toHaveKey(Str::camel($trophy->metric->value));
    }
});

it('lets a grandparent see how the child is doing', function () {
    [, $child] = family();

    $this->actingAs(viewer($child), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/progress")
        ->assertOk();
});

it('keeps progress and rewards away from outside the family', function () {
    [, $child] = family();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/progress")
        ->assertForbidden();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/rewards")
        ->assertForbidden();
});

it('has no rewards to show before any are earned', function () {
    [, $child] = family();

    $this->getJson("/api/v1/children/{$child->id}/rewards")
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
