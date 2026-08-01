<?php

declare(strict_types=1);

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

    ownMilestone($child, $chapter->id, ['months_from' => 217])->assertJsonValidationErrorFor('months_from');
    ownMilestone($child, $chapter->id, ['months_from' => -1])->assertJsonValidationErrorFor('months_from');
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
        ->and($response->json('data.isDeletable'))->toBeTrue()
        ->and($response->json('data.sortOrder'))->toBe($last + 10);
});

it('gives a milestone the age of the chapter it was added to', function () {
    [, $child] = family(ageMonths: 12);
    $chapter = $child->chapters()->whereNotNull('months_from')->where('months_from', '<=', 12)
        ->orderByDesc('months_from')->first();

    ownMilestone($child, $chapter->id)
        ->assertCreated()
        ->assertJsonPath('data.monthsFrom', $chapter->months_from);
});

it('keeps the age the parent chose when they set one', function () {
    [, $child] = family(ageMonths: 12);
    $chapter = $child->chapters()->first();

    ownMilestone($child, $chapter->id, ['months_from' => 3])
        ->assertCreated()
        ->assertJsonPath('data.monthsFrom', 3);
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

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$stranger->id}/hide")
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
