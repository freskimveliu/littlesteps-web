<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Enums\PropertyKey;
use App\Models\Child;
use App\Models\ChildMilestone;
use Illuminate\Testing\TestResponse;

function ownMilestone(Child $child, int $chapterId, array $overrides = []): TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapterId,
        'name' => 'First trip to the sea',
        ...$overrides,
    ]);
}

/** A milestone belonging to a different child of the same parent. */
function otherChildsMilestone(Child $child): ChildMilestone
{
    $other = Child::factory()->bornMonthsAgo(6)->create(['created_by_user_id' => $child->created_by_user_id]);

    $chapter = $other->chapters()->create([
        'name' => 'Somebody else’s',
        'sort_order' => 10,
        'is_editable' => true,
    ]);

    return $other->milestones()->create([
        'child_chapter_id' => $chapter->id,
        'name' => 'Not yours',
        'xp' => 20,
        'sort_order' => 10,
        'is_editable' => true,
    ]);
}

it('refuses a milestone the parent has not named', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, ['name' => null])->assertJsonValidationErrorFor('name');
    ownMilestone($child, $chapter->id, ['name' => str_repeat('a', 81)])->assertJsonValidationErrorFor('name');
});

it('refuses a milestone icon the app cannot draw and a category that does not exist', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, ['icon' => 'not-an-ionicon'])->assertJsonValidationErrorFor('icon');
    ownMilestone($child, $chapter->id, ['category_id' => 9999])->assertJsonValidationErrorFor('category_id');
});

it('refuses a milestone age beyond the end of childhood', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, ['happens_after' => 6571])->assertJsonValidationErrorFor('happens_after');
    ownMilestone($child, $chapter->id, ['happens_after' => -1])->assertJsonValidationErrorFor('happens_after');
});

it('refuses a milestone asking for more than ten measurements', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, [
        'properties' => collect(range(1, 11))
            ->map(fn (int $i) => ['key' => PropertyKey::Custom->value, 'name' => "Measurement {$i}"])
            ->all(),
    ])->assertJsonValidationErrorFor('properties');
});

it('refuses a measurement the growth chart has never heard of', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, [
        'properties' => [['key' => 'shoe-size', 'name' => 'Shoe size']],
    ])->assertJsonValidationErrorFor('properties.0.key');
});

it('gives a milestone the parent wrote its own xp and a place at the end', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();
    $last = $chapter->milestones()->max('sort_order');

    $response = ownMilestone($child, $chapter->id)->assertCreated();

    expect($response->json('data.xp'))->toBe(20)
        ->and($response->json('data.isEditable'))->toBeTrue()
        ->and($response->json('data.abilities.delete'))->toBeTrue()
        ->and($response->json('data.sortOrder'))->toBe($last + 10);
});

it('gives a milestone the age of the chapter it was added to', function () {
    [, $child] = family(ageMonths: 12);
    $chapter = $child->chapters()->whereNotNull('months_from')->where('months_from', '<=', 12)
        ->orderByDesc('months_from')->first();

    ownMilestone($child, $chapter->id)
        ->assertCreated()
        ->assertJsonPath('data.happensAfter', $chapter->months_from)
        ->assertJsonPath('data.happensUnit', 'months');
});

it('keeps the age the parent chose when they set one', function () {
    [, $child] = family(ageMonths: 12);
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, ['happens_after' => 3, 'happens_unit' => 'months'])
        ->assertCreated()
        ->assertJsonPath('data.happensAfter', 3);
});

it('refuses a milestone added to a chapter belonging to another child', function () {
    [, $child] = family();
    $stranger = otherChildsMilestone($child);

    ownMilestone($child, $stranger->child_chapter_id)->assertNotFound();
});

it('never reaches a milestone through the wrong child', function () {
    [, $child] = family();
    $stranger = otherChildsMilestone($child);

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$stranger->id}", ['name' => 'Mine now'])
        ->assertNotFound();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$stranger->id}")
        ->assertNotFound();

    expect($stranger->fresh()->name)->toBe('Not yours');
});

it('renames a milestone the parent wrote', function () {
    [, $child] = family();
    $chapter = $child->chapters()->first();
    $id = ownMilestone($child, $chapter->id)->assertCreated()->json('data.id');

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$id}", [
        'name' => 'First paddle in the sea',
        'icon' => 'balloon-outline',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'First paddle in the sea')
        ->assertJsonPath('data.icon', 'balloon-outline');
});

it('moves a milestone the parent wrote into another chapter the child has reached', function () {
    [$user, $child] = family(ageMonths: 12);
    $chapter = $child->chapters()->orderBy('sort_order')->first();
    $id = ownMilestone($child, $chapter->id)->assertCreated()->json('data.id');

    $target = $child->chapters()
        ->where('id', '!=', $chapter->id)
        ->where(fn ($q) => $q->whereNull('months_from')->orWhere('months_from', '<=', 12))
        ->orderBy('sort_order')
        ->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$id}", [
        'child_chapter_id' => $target->id,
    ])->assertOk();

    $milestone = $child->milestones()->find($id);

    expect($milestone->child_chapter_id)->toBe($target->id)
        ->and($milestone->updated_by_user_id)->toBe($user->id);
});

it('pins a milestone that names a date to the chapter that is that date', function () {
    [, $child] = family(ageMonths: 12);
    $dated = $child->milestones()->where('name', 'Month 5')->first();
    $target = $child->chapters()->where('months_from', 6)->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$dated->id}", [
        'child_chapter_id' => $target->id,
    ])->assertJsonValidationErrorFor('child_chapter_id');

    expect($dated->fresh()->child_chapter_id)->not->toBe($target->id);
});

it('lets a guided first be moved, because it happens whenever it happens', function () {
    [, $child] = family(ageMonths: 12);
    $first = $child->milestones()->where('name', 'Coming Home')->first();
    $target = $child->chapters()->where('months_from', 6)->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$first->id}", [
        'child_chapter_id' => $target->id,
    ])->assertOk();

    expect($first->fresh()->child_chapter_id)->toBe($target->id);
});

it('refuses to move a dated milestone in time', function () {
    [, $child] = family(ageMonths: 12);
    $dated = $child->milestones()->where('name', 'First Birthday!')->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$dated->id}", ['happens_after' => 30])
        ->assertJsonValidationErrorFor('happens_after');

    expect($dated->fresh()->happens_after)->toBe(12);
});

it('still lets a dated milestone be renamed and deleted', function () {
    [, $child] = family(ageMonths: 12);
    $dated = $child->milestones()->where('name', 'Month 5')->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$dated->id}", ['name' => 'Five months old'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Five months old')
        ->assertJsonPath('data.abilities.rename', true)
        ->assertJsonPath('data.abilities.delete', true);

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$dated->id}")->assertNoContent();
});

it('refuses to move a milestone the child has not reached in time', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->where('name', 'Little Explorer')->first();
    $milestone = $chapter->milestones()->where('name', 'First Scribble')->first();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", ['happens_after' => 0])
        ->assertJsonValidationErrorFor('happens_after');

    expect($milestone->fresh()->happens_after)->toBe(450);

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id,
        'description' => 'Too soon.',
        'date' => now()->toDateString(),
        'mood' => Mood::Proud->value,
    ])->assertJsonValidationErrorFor('child_milestone_id');
});

it('refuses to move a milestone in time once it holds a memory', function () {
    [, $child] = family(ageMonths: 12);
    $milestone = $child->milestones()->where('name', 'Coming Home')->first();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone->id,
        'description' => 'Home at last.',
        'date' => now()->toDateString(),
        'mood' => Mood::Tender->value,
    ])->assertCreated();

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", ['happens_after' => 12])
        ->assertJsonValidationErrorFor('happens_after');

    expect($milestone->fresh()->happens_after)->toBe(3);
});

it('tells the app when a milestone may be moved in time', function () {
    [, $child] = family(ageMonths: 6);

    $milestones = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data'))
        ->flatMap(fn ($chapter) => $chapter['milestones'])
        ->keyBy('name');

    expect($milestones['Coming Home']['abilities']['retime'])->toBeTrue()
        ->and($milestones['Month 5']['abilities']['retime'])->toBeFalse()
        ->and($milestones['First Scribble']['abilities']['retime'])->toBeFalse();
});

it('refuses to record into a chapter the child has not reached', function () {
    [, $child] = family(ageMonths: 6);

    $chapter = $this->postJson("/api/v1/children/{$child->id}/chapters", [
        'name' => 'School days', 'months_from' => 0,
    ])->assertCreated()->json('data.id');

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter, 'name' => 'First day',
    ])->assertCreated()->json('data.id');

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$chapter}", ['months_from' => 60])
        ->assertOk();

    $returned = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->json('data'))
        ->firstWhere('id', $chapter);

    expect(collect($returned['milestones'])->firstWhere('id', $milestone)['abilities']['record'])->toBeFalse();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone,
        'description' => 'Not yet.',
        'date' => now()->toDateString(),
        'mood' => Mood::Proud->value,
    ])->assertJsonValidationErrorFor('child_milestone_id');
});

it('tells the app that a date may not be moved and a first may', function () {
    [, $child] = family(ageMonths: 12);

    $milestones = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data'))
        ->flatMap(fn ($chapter) => $chapter['milestones'])
        ->keyBy('name');

    expect($milestones['Month 5']['abilities']['move'])->toBeFalse()
        ->and($milestones['Month 5']['abilities']['reorder'])->toBeFalse()
        ->and($milestones['Coming Home']['abilities']['move'])->toBeTrue()
        ->and($milestones['Coming Home']['abilities']['reorder'])->toBeTrue();
});
