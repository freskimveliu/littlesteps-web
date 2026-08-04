<?php

declare(strict_types=1);

use App\Enums\Mood;

it('takes a deleted milestone off the map and out of the counts', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $before = $chapter->milestones()->count();
    $milestone = $chapter->milestones()->first();

    $this->deleteJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}")->assertNoContent();

    $returned = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data'))
        ->firstWhere('id', $chapter->id);

    expect(collect($returned['milestones'])->firstWhere('id', $milestone->id))->toBeNull()
        ->and($returned['milestonesTotal'])->toBe($before - 1);
});

it('reorders the milestones in a chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $ids = $chapter->milestones()->orderBy('sort_order')->pluck('id')->all();

    // Only the undated ones may move, so the reversal is of those alone: every
    // dated milestone stays at the index it already held.
    $undated = $chapter->milestones()->where('is_date_editable', true)->orderBy('sort_order')->pluck('id')->all();
    $swapped = array_reverse($undated);
    $order = array_map(
        function ($id) use ($undated, &$swapped) {
            return in_array($id, $undated, true) ? array_shift($swapped) : $id;
        },
        $ids,
    );

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/reorder", [
        'milestones' => $order,
    ])->assertOk();

    expect($chapter->milestones()->orderBy('sort_order')->pluck('id')->all())->toBe($order);
});

it('refuses to shuffle a milestone that names a date', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $ids = $chapter->milestones()->orderBy('sort_order')->pluck('id')->all();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/reorder", [
        'milestones' => array_reverse($ids),
    ])->assertJsonValidationErrorFor('milestones');

    expect($chapter->milestones()->orderBy('sort_order')->pluck('id')->all())->toBe($ids);
});

it('refuses an ordering that is not the chapter it belongs to', function () {
    [, $child] = family(ageMonths: 6);
    [$first, $second] = $child->chapters()->orderBy('sort_order')->take(2)->get();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$first->id}/reorder", [
        'milestones' => $second->milestones()->pluck('id')->all(),
    ])->assertStatus(422);
});

it('moves a chapter the parent wrote to the front of the map', function () {
    [, $child] = family(ageMonths: 6);

    $mine = $this->postJson("/api/v1/children/{$child->id}/chapters", ['name' => 'Our summer by the sea'])
        ->assertCreated()
        ->json('data.id');

    $guided = $child->chapters()->where('id', '!=', $mine)->orderBy('sort_order')->pluck('id')->all();
    $order = [$mine, ...$guided];

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", ['chapters' => $order])
        ->assertOk()
        ->assertJsonPath('data.0.id', $mine);

    expect($child->chapters()->orderBy('sort_order')->pluck('id')->all())->toBe($order);
});

it('refuses to shuffle the guided chapters out of their age order', function () {
    [, $child] = family(ageMonths: 6);
    $ids = $child->chapters()->orderBy('sort_order')->pluck('id')->all();

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", ['chapters' => array_reverse($ids)])
        ->assertJsonValidationErrorFor('chapters');

    expect($child->chapters()->orderBy('sort_order')->pluck('id')->all())->toBe($ids);
});

it('refuses a chapter ordering that is not this child', function () {
    [, $other] = family(ageMonths: 6);
    $strangers = $other->chapters()->pluck('id')->all();

    // Signs in as the second parent, so the request below is theirs.
    [, $child] = family(ageMonths: 6);

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", ['chapters' => $strangers])
        ->assertStatus(422);
});

it('refuses to move a milestone into a chapter the child has not reached', function () {
    [$user, $child] = family(ageMonths: 6);

    $locked = $child->chapters()->where('months_from', '>', $child->ageInMonths())->first();
    $milestone = $child->milestones()->create([
        'child_chapter_id' => $child->chapters()->first()->id,
        'name' => 'Ours',
        'sort_order' => 10,
        'is_editable' => true,
        'created_by_user_id' => $user->id,
    ]);

    $this->patchJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}", [
        'child_chapter_id' => $locked->id,
    ])->assertJsonValidationErrorFor('child_chapter_id');

    expect($milestone->fresh()->child_chapter_id)->not->toBe($locked->id);
});

it('refuses to add a milestone to a chapter the child has not reached', function () {
    [, $child] = family(ageMonths: 6);
    $locked = $child->chapters()->where('months_from', '>', $child->ageInMonths())->first();

    $this->postJson("/api/v1/children/{$child->id}/milestones", [
        'child_chapter_id' => $locked->id,
        'name' => 'Too soon',
    ])->assertJsonValidationErrorFor('child_chapter_id');
});

it('adds a chapter of the parents own, worth no xp', function () {
    [, $child] = family(ageMonths: 6);

    $this->postJson("/api/v1/children/{$child->id}/chapters", [
        'name' => 'Our summer',
        'description' => 'The one with the sea',
        'icon' => 'sunny',
    ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Our summer')
        ->assertJsonPath('data.xp', 0)
        ->assertJsonPath('data.isEditable', true);
});

it('renames a chapter whether the parent wrote it or the catalogue did', function () {
    [, $child] = family(ageMonths: 6);

    $own = $this->postJson("/api/v1/children/{$child->id}/chapters", ['name' => 'Ours'])
        ->assertCreated()
        ->json('data.id');

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$own}", ['name' => 'Ours, renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Ours, renamed');

    $guided = $child->chapters()->where('is_editable', false)->first();

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$guided->id}", ['name' => 'Ours now'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Ours now')
        ->assertJsonPath('data.abilities.rename', true);
});

it('deletes an empty chapter and refuses one holding a memory', function () {
    [$user, $child] = family(ageMonths: 6);

    $chapter = $child->chapters()->create([
        'name' => 'Ours',
        'sort_order' => 999,
        'is_editable' => true,
        'created_by_user_id' => $user->id,
    ]);

    $milestone = $child->milestones()->create([
        'child_chapter_id' => $chapter->id,
        'name' => 'A day out',
        'sort_order' => 10,
        'is_editable' => true,
        'created_by_user_id' => $user->id,
    ]);

    $child->entries()->create([
        'child_milestone_id' => $milestone->id,
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful,
        'created_by_user_id' => $user->id,
    ]);

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}")->assertForbidden();

    $target = $child->chapters()->where('is_editable', false)->first();

    $this->deleteJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}", [
        'move_milestones_to' => $target->id,
    ])->assertNoContent();

    expect($milestone->fresh()->child_chapter_id)->toBe($target->id);
});

it('hands back the same prompt all day', function () {
    [, $child] = family(ageMonths: 6);

    $first = $this->getJson("/api/v1/children/{$child->id}/prompt")->assertOk()->json('data.id');
    $again = $this->getJson("/api/v1/children/{$child->id}/prompt")->assertOk()->json('data.id');

    expect($again)->toBe($first);
});

it('refuses to change the age a guided chapter opens at', function () {
    [, $child] = family(ageMonths: 6);
    $guided = $child->chapters()->where('months_from', 6)->first();

    $this->patchJson("/api/v1/children/{$child->id}/chapters/{$guided->id}", ['months_from' => 24])
        ->assertJsonValidationErrorFor('months_from');

    expect($guided->fresh()->months_from)->toBe(6);
});

it('tells the app that a guided chapter may not be reordered and its own may', function () {
    [, $child] = family(ageMonths: 6);

    $mine = $this->postJson("/api/v1/children/{$child->id}/chapters", ['name' => 'Our summer by the sea'])
        ->assertCreated()
        ->json('data.id');

    $chapters = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data'))
        ->keyBy('id');

    expect($chapters[$mine]['abilities']['reorder'])->toBeTrue()
        ->and($chapters->firstWhere('name', 'On the Move')['abilities']['reorder'])->toBeFalse();
});

it('offers nothing to rearrange in a chapter the child has not reached', function () {
    [, $child] = family(ageMonths: 6);

    $locked = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data'))
        ->firstWhere('isUnlocked', false);

    expect($locked)->not->toBeNull()
        ->and($locked['abilities']['reorder'])->toBeFalse()
        ->and(collect($locked['milestones'])->every(fn ($m) => $m['abilities']['reorder'] === false))->toBeTrue()
        ->and(collect($locked['milestones'])->every(fn ($m) => $m['abilities']['move'] === false))->toBeTrue();
});

it('refuses to reorder the milestones of a chapter that has not opened', function () {
    [, $child] = family(ageMonths: 6);
    $locked = $child->chapters()->where('months_from', '>', $child->ageInMonths())->orderBy('sort_order')->first();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$locked->id}/reorder", [
        'milestones' => array_reverse($locked->milestones()->orderBy('sort_order')->pluck('id')->all()),
    ])->assertForbidden();
});

it('refuses to shuffle a chapter of the parents own that the child has not reached', function () {
    [$user, $child] = family(ageMonths: 6);

    $ahead = $child->chapters()->create([
        'name' => 'When she starts school',
        'months_from' => 60,
        'sort_order' => 999,
        'is_editable' => true,
        'created_by_user_id' => $user->id,
    ]);

    $chapters = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data'));

    expect($chapters->firstWhere('id', $ahead->id)['abilities']['reorder'])->toBeFalse();

    $ids = $child->chapters()->orderBy('sort_order')->pluck('id')->all();
    $order = [$ahead->id, ...array_values(array_diff($ids, [$ahead->id]))];

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", ['chapters' => $order])
        ->assertJsonValidationErrorFor('chapters');
});

it('refuses a reordering that leaves milestones out', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $ids = $chapter->milestones()->orderBy('sort_order')->pluck('id')->all();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/reorder", [
        'milestones' => array_slice($ids, 0, 3),
    ])->assertJsonValidationErrorFor('milestones');

    expect($chapter->milestones()->orderBy('sort_order')->pluck('id')->all())->toBe($ids);
});
