<?php

declare(strict_types=1);

use App\Enums\MemberRole;
use App\Enums\Mood;
use App\Enums\Relation;
use App\Models\User;
use Illuminate\Testing\TestResponse;

function share(int $childId, User $with, string $role = 'viewer'): TestResponse
{
    return test()->postJson("/api/v1/children/{$childId}/members", [
        'share_code' => $with->share_code,
        'relation' => Relation::Grandparent->value,
        'role' => $role,
    ]);
}

it('lets a grandparent in by the code they read out', function () {
    [, $child] = family();
    $nana = User::factory()->create(['name' => 'Nana']);

    share($child->id, $nana)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Nana')
        ->assertJsonPath('data.role', MemberRole::Viewer->value);

    $this->actingAs($nana, 'sanctum')
        ->getJson('/api/v1/children')
        ->assertOk()
        ->assertJsonPath('data.0.id', $child->id);
});

it('takes a lowercase code, since it is typed by hand', function () {
    [, $child] = family();
    $nana = User::factory()->create();

    $this->postJson("/api/v1/children/{$child->id}/members", [
        'share_code' => strtolower($nana->share_code),
        'relation' => Relation::Grandparent->value,
        'role' => 'viewer',
    ])->assertCreated();
});

it('says the same thing to a code that does not exist as to its own', function () {
    [$owner, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/members", [
        'share_code' => 'ZZZZZZ',
        'relation' => Relation::Grandparent->value,
        'role' => 'viewer',
    ])->assertJsonValidationErrorFor('share_code');

    share($child->id, $owner)->assertJsonValidationErrorFor('share_code');
});

it('refuses the same person twice', function () {
    [, $child] = family();
    $nana = User::factory()->create();

    share($child->id, $nana)->assertCreated();
    share($child->id, $nana)->assertJsonValidationErrorFor('share_code');
});

it('lets only the parent who started it share', function () {
    [, $child] = family();
    $nana = User::factory()->create();

    $this->actingAs(editor($child), 'sanctum');

    share($child->id, $nana)->assertForbidden();
});

it('promotes a viewer to an editor and back', function () {
    [, $child] = family();
    $nana = User::factory()->create();

    $member = share($child->id, $nana)->assertCreated()->json('data.id');

    $this->patchJson("/api/v1/children/{$child->id}/members/{$member}", ['role' => 'editor'])
        ->assertOk()
        ->assertJsonPath('data.role', MemberRole::Editor->value);

    $this->actingAs($nana, 'sanctum')
        ->postJson("/api/v1/children/{$child->id}/entries", [
            'description' => 'She was so proud.',
            'date' => now()->toDateString(),
            'mood' => Mood::Proud->value,
        ])->assertCreated();
});

it('will not touch the place of the parent who started it', function () {
    [$owner, $child] = family();

    $theirs = $child->memberships()->where('user_id', $owner->id)->firstOrFail();

    $this->patchJson("/api/v1/children/{$child->id}/members/{$theirs->id}", ['role' => 'viewer'])
        ->assertForbidden();

    $this->deleteJson("/api/v1/children/{$child->id}/members/{$theirs->id}")
        ->assertForbidden();
});

it('lets the parent show somebody the door', function () {
    [, $child] = family();
    $nana = User::factory()->create();

    $member = share($child->id, $nana)->assertCreated()->json('data.id');

    $this->deleteJson("/api/v1/children/{$child->id}/members/{$member}")->assertNoContent();

    $this->actingAs($nana, 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertForbidden();
});

it('lets somebody show themselves out', function () {
    [, $child] = family();
    $nana = User::factory()->create();

    $member = share($child->id, $nana)->assertCreated()->json('data.id');

    $this->actingAs($nana, 'sanctum')
        ->deleteJson("/api/v1/children/{$child->id}/members/{$member}")
        ->assertNoContent();
});

it('will not let one guest remove another', function () {
    [, $child] = family();
    $nana = User::factory()->create();
    $grandad = User::factory()->create();

    $nanasRow = share($child->id, $nana)->assertCreated()->json('data.id');
    share($child->id, $grandad)->assertCreated();

    $this->actingAs($grandad, 'sanctum')
        ->deleteJson("/api/v1/children/{$child->id}/members/{$nanasRow}")
        ->assertForbidden();
});

it('lists the family to everybody in it', function () {
    [, $child] = family();
    $nana = User::factory()->create(['name' => 'Nana']);
    share($child->id, $nana)->assertCreated();

    $names = $this->actingAs($nana, 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/members")
        ->assertOk()
        ->json('data.*.name');

    expect($names)->toContain('Nana');
});

it('tells a guest they may not share, and the parent that they may', function () {
    [, $child] = family();
    $nana = User::factory()->create();
    share($child->id, $nana)->assertCreated();

    $asParent = $this->getJson("/api/v1/children/{$child->id}/members")->assertOk()->json('data');
    $guestRow = collect($asParent)->firstWhere('isOwner', false);

    expect($guestRow['abilities'])->toBe(['edit' => true, 'remove' => true]);

    $asGuest = $this->actingAs($nana, 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/members")
        ->assertOk()
        ->json('data');

    expect(collect($asGuest)->firstWhere('isYou', true)['abilities'])
        ->toBe(['edit' => false, 'remove' => true])
        ->and(collect($asGuest)->firstWhere('isOwner', true)['abilities'])
        ->toBe(['edit' => false, 'remove' => false]);
});

it('lets anybody say how they are related to the child', function () {
    [$owner, $child] = family();
    $nana = User::factory()->create();
    $member = share($child->id, $nana)->assertCreated()->json('data.id');

    $this->actingAs($nana, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}/members/{$member}", [
            'relation' => Relation::AuntUncle->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.relation', Relation::AuntUncle->value);

    // Including the parent who started it, whose own row is otherwise untouchable.
    $theirs = $child->memberships()->where('user_id', $owner->id)->firstOrFail();

    $this->actingAs($owner, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}/members/{$theirs->id}", [
            'relation' => Relation::Father->value,
        ])
        ->assertOk()
        ->assertJsonPath('data.relation', Relation::Father->value);
});

it('will not let a guest promote themselves', function () {
    [, $child] = family();
    $nana = User::factory()->create();
    $member = share($child->id, $nana)->assertCreated()->json('data.id');

    $this->actingAs($nana, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}/members/{$member}", ['role' => 'editor'])
        ->assertForbidden();
});

it('will not let one guest rename another', function () {
    [, $child] = family();
    $nana = User::factory()->create();
    $grandad = User::factory()->create();

    $nanasRow = share($child->id, $nana)->assertCreated()->json('data.id');
    share($child->id, $grandad)->assertCreated();

    $this->actingAs($grandad, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}/members/{$nanasRow}", [
            'relation' => Relation::Other->value,
        ])
        ->assertForbidden();
});

it('says on the child itself who may hand out the key', function () {
    [, $child] = family();
    $nana = User::factory()->create();
    share($child->id, $nana, 'editor')->assertCreated();

    $this->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.abilities.share', true);

    // An editor writes memories and still cannot let anybody else in.
    $this->actingAs($nana, 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.abilities.share', false)
        ->assertJsonPath('data.abilities.contribute', true);
});

it('says who wrote a memory and who last changed it', function () {
    [$owner, $child] = family();
    $nana = User::factory()->create(['name' => 'Nana']);
    $member = share($child->id, $nana, 'editor')->assertCreated()->json('data.id');

    $entry = $this->actingAs($nana, 'sanctum')
        ->postJson("/api/v1/children/{$child->id}/entries", [
            'description' => 'She waved at me.',
            'date' => now()->toDateString(),
            'mood' => Mood::Tender->value,
        ])->assertCreated();

    expect($entry->json('data.entry.createdBy.name'))->toBe('Nana')
        ->and($entry->json('data.entry.createdBy.isYou'))->toBeTrue();

    $id = $entry->json('data.entry.id');

    $edited = $this->actingAs($owner, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}/entries/{$id}", ['description' => 'She waved at us.'])
        ->assertOk();

    expect($edited->json('data.createdBy.name'))->toBe('Nana')
        ->and($edited->json('data.createdBy.isYou'))->toBeFalse()
        ->and($edited->json('data.updatedBy.name'))->toBe($owner->name)
        ->and($member)->not->toBeNull();
});
