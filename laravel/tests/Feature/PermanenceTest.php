<?php

declare(strict_types=1);

use App\Enums\Mood;

it('lets a parent delete their own milestone while it is still empty', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'First trip to the sea',
    ])->assertCreated()->json('data.id');

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone}")->assertNoContent();
});

it('keeps the memory and the xp when a recorded milestone is deleted', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'First trip to the sea',
    ])->json('data.id');

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone,
        'date' => now()->toDateString(),
        'description' => 'Sand everywhere.',
        'mood' => Mood::Joyful->value,
    ])->assertCreated()->json('data.entry.id');

    $xp = $child->fresh()->xp;

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone}")->assertNoContent();

    expect($child->entries()->whereKey($entry)->value('child_milestone_id'))->toBeNull()
        ->and($child->entries()->whereKey($entry)->exists())->toBeTrue()
        ->and($child->fresh()->xp)->toBe($xp);
});

it('lets a guided milestone be renamed — the map is the parent\'s', function () {
    [, $child] = family(ageMonths: 6);
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", ['name' => 'The day we met'])
        ->assertOk()
        ->assertJsonPath('data.name', 'The day we met')
        ->assertJsonPath('data.abilities.rename', true);
});

it('lets a guided first be filed in another chapter, but never a date', function () {
    [, $child] = family(ageMonths: 6);
    $first = $child->milestones()->where('name', 'Coming Home')->first();
    $dated = $child->milestones()->where('name', 'Birth Day')->first();
    $elsewhere = $child->chapters()->where('months_from', 3)->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$first->id}", [
        'child_chapter_id' => $elsewhere->id,
    ])->assertOk()->assertJsonPath('data.chapterId', $elsewhere->id);

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$dated->id}", [
        'child_chapter_id' => $elsewhere->id,
    ])->assertJsonValidationErrorFor('child_chapter_id');
});

it('lets a guided milestone that will never happen be deleted while it is empty', function () {
    [, $child] = family();
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    expect($milestone->is_editable)->toBeFalse();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}")->assertNoContent();

    expect($child->milestones()->whereKey($milestone->id)->exists())->toBeFalse();
});

it('takes a recorded guided milestone off the map and leaves its memory in the story', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id, 'date' => now()->toDateString(),
        'description' => 'Day one.', 'mood' => Mood::Tender->value,
    ])->assertCreated()->json('data.entry.id');

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}")->assertNoContent();

    expect($child->milestones()->whereKey($milestone->id)->exists())->toBeFalse()
        ->and($child->entries()->whereKey($entry)->exists())->toBeTrue();

    $timeline = $this->getJson("/api/v1/children/{$child->id}/entries")->assertOk()->json('data.items');

    expect(collect($timeline)->firstWhere('id', $entry))->not->toBeNull();
});

it('keeps a memory attached to a milestone forever, but allows editing it', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id,
        'date' => now()->toDateString(),
        'description' => 'The longest night.',
        'mood' => Mood::Tender->value,
    ])->assertCreated()->assertJsonPath('data.entry.abilities.delete', false)->json('data.entry.id');

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}")->assertForbidden();

    $this->patchJson("/api/v1/children/{$child->id}/entries/{$entry}", [
        'description' => 'The longest, best night.',
    ])->assertOk()->assertJsonPath('data.description', 'The longest, best night.');
});

it('lets a free memory be deleted', function () {
    [, $child] = family();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She found her feet today.',
        'date' => now()->toDateString(),
        'mood' => Mood::Proud->value,
    ])->assertCreated()->assertJsonPath('data.entry.abilities.delete', true)->json('data.entry.id');

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}")->assertNoContent();
});

it('keeps the xp when a free memory is deleted', function () {
    [, $child] = family();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'A good day.', 'date' => now()->toDateString(), 'mood' => Mood::Joyful->value,
    ])->json('data.entry.id');

    expect($child->fresh()->xp)->toBe(10);

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}")->assertNoContent();

    expect($child->fresh()->xp)->toBe(10);
});

it('lets a viewer look but not write', function () {
    [, $child] = family();
    $viewer = viewer($child);

    $this->actingAs($viewer, 'sanctum');

    $this->getJson("/api/v1/children/{$child->id}")->assertOk();
    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'Trying anyway.', 'date' => now()->toDateString(), 'mood' => Mood::Joyful->value,
    ])->assertForbidden();
});

it('lets only the creator edit or delete the child', function () {
    [, $child] = family();
    $viewer = viewer($child);

    $this->actingAs($viewer, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}", ['name' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($viewer, 'sanctum')
        ->deleteJson("/api/v1/children/{$child->id}")
        ->assertForbidden();
});
