<?php

declare(strict_types=1);

use App\Enums\Mood;

it('returns hidden chapters and milestones so a parent can change their mind', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $milestone = $chapter->milestones()->first();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}/hide")->assertOk();

    $body = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data');
    $returned = collect($body)->firstWhere('id', $chapter->id);
    $hidden = collect($returned['milestones'])->firstWhere('id', $milestone->id);

    expect($hidden)->not->toBeNull()
        ->and($hidden['isHidden'])->toBeTrue();
});

it('leaves hidden milestones out of the counts', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $before = $chapter->milestones()->count();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$chapter->milestones()->first()->id}/hide")
        ->assertOk();

    $body = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk()->json('data');

    expect(collect($body)->firstWhere('id', $chapter->id)['milestonesTotal'])->toBe($before - 1);
});

it('restores a hidden milestone', function () {
    [, $child] = family(ageMonths: 6);
    $milestone = $child->chapters()->first()->milestones()->first();

    $this->postJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}/hide")->assertOk();
    $this->postJson("/api/v1/children/{$child->id}/milestones/{$milestone->id}/hide", ['hidden' => false])
        ->assertOk()
        ->assertJsonPath('data.isHidden', false);
});

it('reorders the milestones in a chapter', function () {
    [, $child] = family(ageMonths: 6);
    $chapter = $child->chapters()->first();
    $ids = $chapter->milestones()->orderBy('sort_order')->pluck('id')->all();

    $reversed = array_reverse($ids);

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$chapter->id}/reorder", [
        'milestones' => $reversed,
    ])->assertOk();

    expect($chapter->milestones()->orderBy('sort_order')->pluck('id')->all())->toBe($reversed);
});

it('refuses an ordering that is not the chapter it belongs to', function () {
    [, $child] = family(ageMonths: 6);
    [$first, $second] = $child->chapters()->orderBy('sort_order')->take(2)->get();

    $this->postJson("/api/v1/children/{$child->id}/chapters/{$first->id}/reorder", [
        'milestones' => $second->milestones()->pluck('id')->all(),
    ])->assertStatus(422);
});

it('reorders the chapters themselves', function () {
    [, $child] = family(ageMonths: 6);
    $ids = $child->chapters()->orderBy('sort_order')->pluck('id')->all();

    $reversed = array_reverse($ids);

    $this->postJson("/api/v1/children/{$child->id}/chapters/reorder", ['chapters' => $reversed])
        ->assertOk()
        ->assertJsonPath('data.0.id', $reversed[0]);

    expect($child->chapters()->orderBy('sort_order')->pluck('id')->all())->toBe($reversed);
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
