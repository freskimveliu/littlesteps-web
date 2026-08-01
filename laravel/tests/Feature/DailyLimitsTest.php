<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\Mood;
use App\Models\AppSetting;

function freeMemory(App\Models\Child $child, array $overrides = []): Illuminate\Testing\TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
        ...$overrides,
    ]);
}

/** Every memory has to carry a mood and either words or a photo. */
function milestoneMemory(App\Models\Child $child, int $milestoneId, array $overrides = []): Illuminate\Testing\TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestoneId,
        'description' => 'One for the album.',
        'date' => now()->toDateString(),
        'mood' => Mood::Proud->value,
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

it('allows five milestone memories a day, each worth its own milestone', function () {
    [, $child] = family(ageMonths: 12);

    $milestones = $child->milestones()->whereNull('months_from')->orWhere('months_from', '<=', 12)
        ->orderBy('sort_order')->take(6)->get();

    foreach ($milestones->take(5) as $milestone) {
        milestoneMemory($child, $milestone->id)->assertCreated();
    }

    milestoneMemory($child, $milestones->last()->id)->assertJsonValidationErrorFor('date');

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

it('caps how many of a parent own milestones one chapter can hold', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    AppSetting::where('key', AppSettingKey::MaxCustomMilestonesPerChapter->value)->update(['value' => '2']);

    foreach (range(1, 2) as $i) {
        $this->postJson("/api/v1/children/{$child->id}/milestones", [
            'child_chapter_id' => $chapter->id,
            'name' => "Our own milestone {$i}",
        ])->assertCreated();
    }

    $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'One too many',
    ])->assertJsonValidationErrorFor('child_chapter_id');
});

it('refuses a second memory on the same milestone', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    milestoneMemory($child, $milestone->id)->assertCreated();
    milestoneMemory($child, $milestone->id)->assertJsonValidationErrorFor('child_milestone_id');
});

it('refuses a memory on a milestone the child is too young for', function () {
    [, $child] = family(ageMonths: 1);
    $milestone = $child->milestones()->where('name', 'Month 6')->first();

    milestoneMemory($child, $milestone->id)->assertJsonValidationErrorFor('child_milestone_id');
});
