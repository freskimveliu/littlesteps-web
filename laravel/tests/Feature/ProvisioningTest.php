<?php

declare(strict_types=1);

use App\Models\ChildMilestone;
use App\Models\ChildStep;
use App\Models\ChildStepProperty;

beforeEach(fn () => seedCatalogue());

it('copies the whole catalogue onto a new child', function () {
    [, $child] = family();

    expect($child->milestones()->count())->toBe(8)
        ->and($child->steps()->count())->toBe(118)
        ->and(ChildStepProperty::whereIn('child_step_id', $child->steps()->select('id'))->count())->toBe(65);
});

it('copies the words rather than joining them, so an admin edit cannot rewrite a saved journey', function () {
    [, $child] = family();

    $step = $child->steps()->where('name', 'Birth Day')->first();
    $step->template_step_id and $step->templateStep = null;

    App\Models\TemplateStep::where('name', 'Birth Day')->update(['name' => 'Renamed by admin']);

    expect($child->steps()->find($step->id)->name)->toBe('Birth Day')
        ->and($step->template_step_id)->not->toBeNull();
});

it('provisions each child its own copy', function () {
    [$user, $first] = family();
    $second = app(App\Actions\Children\CreateChild::class)->handle($user, new App\Data\ChildData(
        name: 'Ari', birthday: now()->subMonths(2)->toDateString(),
        gender: App\Enums\Gender::Boy, relation: App\Enums\Relation::Mother,
    ));

    expect(ChildMilestone::count())->toBe(16)
        ->and(ChildStep::count())->toBe(236)
        ->and($first->steps()->pluck('id')->intersect($second->steps()->pluck('id')))->toBeEmpty();
});

it('makes the creator an editor of their own child', function () {
    [$user, $child] = family();

    expect($child->memberships()->count())->toBe(1)
        ->and($child->memberships->first()->user_id)->toBe($user->id)
        ->and($child->memberships->first()->role)->toBe(App\Enums\MemberRole::Editor);
});

it('returns the map with the flags the app must not compute itself', function () {
    [, $child] = family(ageMonths: 6);

    $response = $this->getJson("/api/v1/children/{$child->id}/milestones")->assertOk();

    $first = $response->json('data.0');

    expect($first)->toHaveKeys(['isCompletable', 'isUnlocked', 'stepsTotal', 'stepsRecorded'])
        ->and($first['steps'][0])->toHaveKeys(['isLocked', 'isRecorded', 'isDeletable', 'isEditable']);
});

it('locks steps the child is not old enough for', function () {
    [, $child] = family(ageMonths: 1);

    $steps = collect($this->getJson("/api/v1/children/{$child->id}/milestones")->json('data'))
        ->flatMap(fn ($chapter) => $chapter['steps']);

    expect($steps->firstWhere('name', 'Birth Day')['isLocked'])->toBeFalse()
        ->and($steps->firstWhere('name', 'Month 6')['isLocked'])->toBeTrue();
});

it('refuses to show a child to someone outside the family', function () {
    [, $child] = family();

    $this->actingAs(App\Models\User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertForbidden();
});
