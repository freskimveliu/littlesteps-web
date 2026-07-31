<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Models\AppSetting;

beforeEach(fn () => seedCatalogue());

function freeMemory(App\Models\Child $child, array $overrides = []): Illuminate\Testing\TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        ...$overrides,
    ]);
}

it('allows one free memory a day', function () {
    [, $child] = family();

    freeMemory($child)->assertCreated();
    freeMemory($child)->assertJsonValidationErrorFor('date');

    expect($child->entries()->count())->toBe(1);
});

it('awards free entry xp from the settings, not from the code', function () {
    [, $child] = family();

    freeMemory($child)->assertCreated()->assertJsonPath('data.xpEarned', 10);

    AppSetting::where('key', AppSettingKey::FreeEntryXp->value)->update(['value' => '25']);
    $child->entries()->delete();

    freeMemory($child)->assertCreated()->assertJsonPath('data.xpEarned', 25);
});

it('allows five step memories a day, each worth its own step', function () {
    [, $child] = family(ageMonths: 12);

    $steps = $child->steps()->whereNull('months_from')->orWhere('months_from', '<=', 12)
        ->orderBy('sort_order')->take(6)->get();

    foreach ($steps->take(5) as $step) {
        test()->postJson("/api/v1/children/{$child->id}/entries", [
            'child_step_id' => $step->id,
            'date' => now()->toDateString(),
        ])->assertCreated();
    }

    test()->postJson("/api/v1/children/{$child->id}/entries", [
        'child_step_id' => $steps->last()->id,
        'date' => now()->toDateString(),
    ])->assertJsonValidationErrorFor('date');

    expect($child->entries()->count())->toBe(5);
});

it('counts the day in the parent timezone, not UTC', function () {
    [$user, $child] = family();
    $user->update(['timezone' => 'Pacific/Kiritimati']);

    $this->travelTo(now()->setTime(23, 30));
    freeMemory($child)->assertCreated();

    // Still the same local day for this parent, even though UTC has not rolled.
    freeMemory($child)->assertJsonValidationErrorFor('date');
});

it('does not let back-dating a photo dodge the limit', function () {
    [, $child] = family();

    freeMemory($child)->assertCreated();
    freeMemory($child, ['date' => now()->subMonth()->toDateString()])->assertJsonValidationErrorFor('date');
});

it('caps how many of a parent own steps one chapter can hold', function () {
    [, $child] = family();
    $milestone = $child->milestones()->first();

    AppSetting::where('key', AppSettingKey::MaxCustomStepsPerMilestone->value)->update(['value' => '2']);

    foreach (range(1, 2) as $i) {
        $this->postJson("/api/v1/children/{$child->id}/steps", [
            'child_milestone_id' => $milestone->id,
            'name' => "Our own step {$i}",
        ])->assertCreated();
    }

    $this->postJson("/api/v1/children/{$child->id}/steps", [
        'child_milestone_id' => $milestone->id,
        'name' => 'One too many',
    ])->assertJsonValidationErrorFor('child_milestone_id');
});

it('refuses a second memory on the same step', function () {
    [, $child] = family(ageMonths: 12);
    $step = $child->steps()->where('name', 'Birth Day')->first();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_step_id' => $step->id, 'date' => now()->toDateString(),
    ])->assertCreated();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_step_id' => $step->id, 'date' => now()->toDateString(),
    ])->assertJsonValidationErrorFor('child_step_id');
});

it('refuses a memory on a step the child is too young for', function () {
    [, $child] = family(ageMonths: 1);
    $step = $child->steps()->where('name', 'Month 6')->first();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_step_id' => $step->id, 'date' => now()->toDateString(),
    ])->assertJsonValidationErrorFor('child_step_id');
});
