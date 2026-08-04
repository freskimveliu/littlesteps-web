<?php

declare(strict_types=1);

use App\Enums\Mood;

/**
 * The birthday is the origin everything is measured from, so it is set once and
 * left alone — see UpdateChildRequest.
 */
it('ignores a birthday sent to the update endpoint', function () {
    [, $child] = family(ageMonths: 6);
    $born = $child->birthday->toDateString();

    $this->patchJson("/api/v1/children/{$child->id}", [
        'name' => 'Liza Rose',
        'birthday' => now()->subMonths(11)->toDateString(),
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Liza Rose')
        ->assertJsonPath('data.ageMonths', 6);

    expect($child->fresh()->birthday->toDateString())->toBe($born);
});

it('keeps a dated memory on the day the calendar fixed', function () {
    [, $child] = family(ageMonths: 14);

    $dated = $child->milestones()->where('name', 'First Birthday!')->firstOrFail();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $dated->id,
        'description' => 'Cake everywhere.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])->assertCreated()->json('data.entry');

    expect($entry['date'])->toBe($child->birthday->copy()->addMonths(12)->toDateString());

    $this->patchJson("/api/v1/children/{$child->id}", [
        'birthday' => now()->subMonths(20)->toDateString(),
    ])->assertOk();

    expect($this->getJson("/api/v1/children/{$child->id}/entries")->json('data.items.0.date'))
        ->toBe($entry['date']);
});
