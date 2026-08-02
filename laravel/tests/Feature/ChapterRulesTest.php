<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Models\Child;
use App\Models\ChildChapter;
use Illuminate\Testing\TestResponse;

function ownChapter(Child $child, array $overrides = []): TestResponse
{
    return test()->postJson("/api/v1/children/{$child->id}/chapters", [
        'name' => 'Our summer',
        ...$overrides,
    ]);
}

/** A chapter belonging to a different child of the same parent. */
function otherChildsChapter(Child $child): ChildChapter
{
    $other = Child::factory()->bornMonthsAgo(6)->create(['created_by_user_id' => $child->created_by_user_id]);

    return $other->chapters()->create([
        'name' => 'Somebody else’s',
        'sort_order' => 10,
        'is_editable' => true,
    ]);
}

it('refuses a chapter the parent has not named', function () {
    [, $child] = family();

    ownChapter($child, ['name' => null])->assertJsonValidationErrorFor('name');
    ownChapter($child, ['name' => str_repeat('a', 81)])->assertJsonValidationErrorFor('name');
});

it('refuses a chapter description longer than a line', function () {
    [, $child] = family();

    ownChapter($child, ['description' => str_repeat('a', 161)])->assertJsonValidationErrorFor('description');
});

it('refuses an icon that is not one the app can draw', function () {
    [, $child] = family();

    ownChapter($child, ['icon' => 'not-an-ionicon'])->assertJsonValidationErrorFor('icon');
});

it('refuses an age beyond the end of childhood', function () {
    [, $child] = family();

    ownChapter($child, ['months_from' => 217])->assertJsonValidationErrorFor('months_from');
    ownChapter($child, ['months_from' => -1])->assertJsonValidationErrorFor('months_from');
});

it('adds a chapter of the parents own at the end of the running order', function () {
    [, $child] = family();
    $last = $child->chapters()->max('sort_order');

    $sortOrder = ownChapter($child)->assertCreated()->json('data.sortOrder');

    expect($sortOrder)->toBe($last + 10);
});

it('saves the description, the icon and the age on a chapter the parent wrote', function () {
    [, $child] = family();
    $id = ownChapter($child)->assertCreated()->json('data.id');

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$id}", [
        'description' => 'The one with the sea in it.',
        'icon' => 'balloon-outline',
        'months_from' => 4,
    ])
        ->assertOk()
        ->assertJsonPath('data.description', 'The one with the sea in it.')
        ->assertJsonPath('data.icon', 'balloon-outline')
        ->assertJsonPath('data.monthsFrom', 4);
});

it('refuses a chapter description longer than a line on an edit too', function () {
    [, $child] = family();
    $id = ownChapter($child)->assertCreated()->json('data.id');

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$id}", [
        'description' => str_repeat('a', 161),
    ])->assertJsonValidationErrorFor('description');
});

it('never reaches a chapter through the wrong child', function () {
    [, $child] = family();
    $stranger = otherChildsChapter($child);

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$stranger->id}", ['name' => 'Mine now'])
        ->assertNotFound();

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$stranger->id}")
        ->assertNotFound();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$stranger->id}/complete")
        ->assertNotFound();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$stranger->id}/reorder", ['milestones' => [1]])
        ->assertNotFound();

    expect($stranger->fresh()->name)->toBe('Somebody else’s');
});

it('carries the milestones over when a chapter is deleted with somewhere to put them', function () {
    [, $child] = family();
    $from = ownChapter($child, ['name' => 'Leaving'])->assertCreated()->json('data.id');
    $to = ownChapter($child, ['name' => 'Staying'])->assertCreated()->json('data.id');

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $from,
        'name' => 'First trip to the sea',
    ])->assertCreated()->json('data.id');

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'child_milestone_id' => $milestone,
        'description' => 'She put her feet in.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
    ])->assertCreated();

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$from}", ['move_steps_to' => $to])
        ->assertNoContent();

    $this->assertDatabaseMissing('child_chapters', ['id' => $from]);

    expect($child->milestones()->find($milestone)->child_chapter_id)->toBe($to)
        ->and($child->entries()->where('child_milestone_id', $milestone)->exists())->toBeTrue();
});

it('refuses to move milestones into a chapter belonging to another child', function () {
    [, $child] = family();
    $from = ownChapter($child)->assertCreated()->json('data.id');
    $stranger = otherChildsChapter($child);

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$from}", ['move_steps_to' => $stranger->id])
        ->assertNotFound();

    $this->assertDatabaseHas('child_chapters', ['id' => $from]);
});

it('refuses to move milestones into the chapter being deleted', function () {
    [, $child] = family();
    $chapter = ownChapter($child)->assertCreated()->json('data.id');

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$chapter}", ['move_steps_to' => $chapter])
        ->assertNotFound();

    $this->assertDatabaseHas('child_chapters', ['id' => $chapter]);
});

it('takes the empty milestones with it when a chapter is deleted', function () {
    [, $child] = family();
    $chapter = ownChapter($child)->assertCreated()->json('data.id');

    $milestone = $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $chapter,
        'name' => 'Never filled in',
    ])->assertCreated()->json('data.id');

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$chapter}")->assertNoContent();

    $this->assertDatabaseMissing('child_milestones', ['id' => $milestone]);
});

it('lets a guided chapter the child has grown past be deleted', function () {
    [, $child] = family(ageMonths: 12);
    $chapter = $child->chapters()->orderBy('sort_order')->first();

    expect($chapter->is_editable)->toBeFalse();

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}")->assertNoContent();

    $this->assertDatabaseMissing('child_chapters', ['id' => $chapter->id]);
    $this->assertDatabaseMissing('child_milestones', ['child_chapter_id' => $chapter->id]);
});

it('refuses to delete a chapter that has already been finished', function () {
    [, $child] = family();
    $chapter = $child->chapters()->orderBy('sort_order')->first();
    $chapter->forceFill(['completed_at' => now()])->save();

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}")->assertForbidden();

    $this->assertDatabaseHas('child_chapters', ['id' => $chapter->id]);
});

it('never leaves a child without a chapter', function () {
    [, $child] = family();
    $last = $child->chapters()->orderBy('sort_order')->first();
    $child->chapters()->whereKeyNot($last->id)->delete();

    $this->getJson("/api/v1/children/{$child->id}/chapters")
        ->assertOk()
        ->assertJsonPath('data.0.isDeletable', false);

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$last->id}")->assertForbidden();
});

it('refuses a reordering that names the same chapter twice', function () {
    [, $child] = family();
    $first = $child->chapters()->orderBy('sort_order')->first();

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", [
        'chapters' => [$first->id, $first->id],
    ])->assertJsonValidationErrorFor('chapters');
});

it('refuses a reordering with nothing in it', function () {
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", ['chapters' => []])
        ->assertJsonValidationErrorFor('chapters');
});
