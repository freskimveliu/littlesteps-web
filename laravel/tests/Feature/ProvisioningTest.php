<?php

declare(strict_types=1);

use App\Actions\Children\CreateChild;
use App\Data\ChildData;
use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\Relation;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use App\Models\ChildMilestoneProperty;
use App\Models\Milestone;
use App\Models\User;

it('copies the whole catalogue onto a new child', function () {
    [, $child] = family();

    expect($child->chapters()->count())->toBe(8)
        ->and($child->milestones()->count())->toBe(118)
        ->and(ChildMilestoneProperty::whereIn('child_milestone_id', $child->milestones()->select('id'))->count())->toBe(65);
});

it('copies the words rather than joining them, so an admin edit cannot rewrite a saved journey', function () {
    [, $child] = family();

    $milestone = $child->milestones()->where('name', 'Birth Day')->first();
    $milestone->milestone_id and $milestone->templateStep = null;

    Milestone::where('name', 'Birth Day')->update(['name' => 'Renamed by admin']);

    expect($child->milestones()->find($milestone->id)->name)->toBe('Birth Day')
        ->and($milestone->milestone_id)->not->toBeNull();
});

it('provisions each child its own copy', function () {
    [$user, $first] = family();
    $second = app(CreateChild::class)->handle($user, new ChildData(
        name: 'Ari', birthday: now()->subMonths(2)->toDateString(),
        gender: Gender::Boy, relation: Relation::Mother,
    ));

    expect(ChildChapter::count())->toBe(16)
        ->and(ChildMilestone::count())->toBe(236)
        ->and($first->milestones()->pluck('id')->intersect($second->milestones()->pluck('id')))->toBeEmpty();
});

it('makes the creator an editor of their own child', function () {
    [$user, $child] = family();

    expect($child->memberships()->count())->toBe(1)
        ->and($child->memberships->first()->user_id)->toBe($user->id)
        ->and($child->memberships->first()->role)->toBe(MemberRole::Editor);
});

it('returns the map with the flags the app must not compute itself', function () {
    [, $child] = family(ageMonths: 6);

    $response = $this->getJson("/api/v1/children/{$child->id}/chapters")->assertOk();

    $first = $response->json('data.0');

    expect($first)->toHaveKeys(['isCompletable', 'isUnlocked', 'stepsTotal', 'stepsRecorded'])
        ->and($first['milestones'][0])->toHaveKeys(['isLocked', 'isRecorded', 'isDeletable', 'isEditable']);
});

it('locks milestones the child is not old enough for', function () {
    [, $child] = family(ageMonths: 1);

    $milestones = collect($this->getJson("/api/v1/children/{$child->id}/chapters")->json('data'))
        ->flatMap(fn ($chapter) => $chapter['milestones']);

    expect($milestones->firstWhere('name', 'Birth Day')['isLocked'])->toBeFalse()
        ->and($milestones->firstWhere('name', 'Month 6')['isLocked'])->toBeTrue();
});

it('refuses to show a child to someone outside the family', function () {
    [, $child] = family();

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertForbidden();
});

it('returns the created child with a level, not a null xp', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/children', [
            'name' => 'Liza',
            'birthday' => now()->subMonths(6)->toDateString(),
            'gender' => 'girl',
            'relation' => 'mother',
        ])
        ->assertCreated()
        ->assertJsonPath('data.xp', 0)
        ->assertJsonPath('data.level.level', 1);
});
