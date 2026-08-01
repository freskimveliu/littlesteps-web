<?php

declare(strict_types=1);

beforeEach(fn () => seedCatalogue());

it('lets a parent delete their own milestone while it is still empty', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'First trip to the sea',
    ])->assertCreated()->json('data.id');

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone}")->assertNoContent();
});

it('refuses to delete a milestone once it holds a memory, so the xp cannot be reclaimed', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter->id,
        'name' => 'First trip to the sea',
    ])->json('data.id');

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone,
        'date' => now()->toDateString(),
    ])->assertCreated();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone}")->assertForbidden();
});

it('never lets a guided milestone be deleted or renamed', function () {
    [, $child] = family();
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}")->assertForbidden();
    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", ['name' => 'Nope'])->assertForbidden();
});

it('keeps a memory attached to a milestone forever, but allows editing it', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id,
        'date' => now()->toDateString(),
        'description' => 'The longest night.',
    ])->assertCreated()->assertJsonPath('data.entry.isDeletable', false)->json('data.entry.id');

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
    ])->assertCreated()->assertJsonPath('data.entry.isDeletable', true)->json('data.entry.id');

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}")->assertNoContent();
});

it('keeps the xp when a free memory is deleted', function () {
    [, $child] = family();

    $entry = $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'A good day.', 'date' => now()->toDateString(),
    ])->json('data.entry.id');

    expect($child->fresh()->xp)->toBe(10);

    $this->deleteJson("/api/v1/children/{$child->id}/entries/{$entry}")->assertNoContent();

    expect($child->fresh()->xp)->toBe(10);
});

it('hides a recorded milestone without touching its memory', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Birth Day')->first();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id, 'date' => now()->toDateString(),
    ])->assertCreated();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}/hide")->assertOk();

    expect($milestone->fresh()->is_hidden)->toBeTrue()
        ->and($child->entries()->where('child_milestone_id', $milestone->id)->exists())->toBeTrue();
});

it('lets a viewer look but not write', function () {
    [, $child] = family();
    $viewer = viewer($child);

    $this->actingAs($viewer, 'sanctum');

    $this->getJson("/api/v1/children/{$child->id}")->assertOk();
    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'Trying anyway.', 'date' => now()->toDateString(),
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
