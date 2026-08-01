<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\Mood;
use App\Enums\Relation;
use App\Models\Child;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;

/** A signed-in parent with no child yet — cheaper than provisioning one. */
function parentOnly(): User
{
    $user = User::factory()->create(['timezone' => 'Europe/Tirane']);

    test()->actingAs($user, 'sanctum');

    return $user;
}

function newChild(array $overrides = []): TestResponse
{
    return test()->postJson('/api/v1/children', [
        'name' => 'Liza',
        'birthday' => now()->subMonths(6)->toDateString(),
        'gender' => Gender::Girl->value,
        'relation' => Relation::Mother->value,
        ...$overrides,
    ]);
}

/** A child belonging to somebody else entirely. */
function strangersChild(): Child
{
    $child = Child::factory()->create(['name' => 'Not yours']);

    $child->memberships()->create([
        'user_id' => $child->created_by_user_id,
        'relation' => Relation::Mother,
        'role' => MemberRole::Editor,
    ]);

    return $child;
}

it('lists only the children a parent can reach', function () {
    [, $child] = family();
    strangersChild();

    $names = $this->getJson('/api/v1/children')->assertOk()->json('data.*.name');

    expect($names)->toBe([$child->name]);
});

it('lists a child shared with a grandparent', function () {
    [, $child] = family();

    $this->actingAs(viewer($child), 'sanctum')
        ->getJson('/api/v1/children')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $child->id);
});

it('orders the children oldest first, so the list does not shuffle', function () {
    $user = parentOnly();

    $younger = Child::factory()->bornMonthsAgo(3)->create(['created_by_user_id' => $user->id, 'name' => 'Ari']);
    $older = Child::factory()->bornMonthsAgo(30)->create(['created_by_user_id' => $user->id, 'name' => 'Liza']);

    foreach ([$younger, $older] as $child) {
        $child->memberships()->create([
            'user_id' => $user->id,
            'relation' => Relation::Mother,
            'role' => MemberRole::Editor,
        ]);
    }

    expect($this->getJson('/api/v1/children')->assertOk()->json('data.*.name'))
        ->toBe(['Liza', 'Ari']);
});

it('refuses a child with no name', function () {
    parentOnly();

    newChild(['name' => null])->assertJsonValidationErrorFor('name');

    expect(Child::count())->toBe(0);
});

it('refuses a name longer than the field can hold', function () {
    parentOnly();

    newChild(['name' => str_repeat('a', 61)])->assertJsonValidationErrorFor('name');

    expect(Child::count())->toBe(0);
});

it('refuses a birthday in the future', function () {
    parentOnly();

    newChild(['birthday' => now()->addDay()->toDateString()])->assertJsonValidationErrorFor('birthday');

    expect(Child::count())->toBe(0);
});

it('refuses a gender or a relation it does not know', function () {
    parentOnly();

    newChild(['gender' => 'unicorn'])->assertJsonValidationErrorFor('gender');
    newChild(['relation' => 'neighbour'])->assertJsonValidationErrorFor('relation');

    expect(Child::count())->toBe(0);
});

it('records the relation the parent chose on their own membership', function () {
    $user = parentOnly();

    $id = newChild(['relation' => Relation::Father->value])->assertCreated()->json('data.id');

    expect(Child::find($id)->memberships()->where('user_id', $user->id)->first()->relation)
        ->toBe(Relation::Father);
});

it('tells the app everything the creator may do', function () {
    [, $child] = family();

    $this->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.isOwner', true)
        ->assertJsonPath('data.isEditable', true)
        ->assertJsonPath('data.isDeletable', true)
        ->assertJsonPath('data.canContribute', true);
});

it('tells the app a viewer may only look', function () {
    [, $child] = family();

    $this->actingAs(viewer($child), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.isOwner', false)
        ->assertJsonPath('data.isEditable', false)
        ->assertJsonPath('data.isDeletable', false)
        ->assertJsonPath('data.canContribute', false);
});

it('tells the app a second parent may add memories but not change the child', function () {
    [, $child] = family();

    $this->actingAs(editor($child), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}")
        ->assertOk()
        ->assertJsonPath('data.isOwner', false)
        ->assertJsonPath('data.isEditable', false)
        ->assertJsonPath('data.isDeletable', false)
        ->assertJsonPath('data.canContribute', true);
});

it('renames a child and keeps the change', function () {
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", ['name' => 'Liza Rose'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Liza Rose');

    expect($child->fresh()->name)->toBe('Liza Rose');
});

it('recomputes the age when a birthday is corrected', function () {
    [, $child] = family(ageMonths: 6);

    $this->patchJson("/api/v1/children/{$child->id}", [
        'birthday' => now()->subMonths(11)->toDateString(),
    ])
        ->assertOk()
        ->assertJsonPath('data.ageMonths', 11);
});

it('refuses an update that would put the birthday in the future', function () {
    [, $child] = family();

    $this->patchJson("/api/v1/children/{$child->id}", ['birthday' => now()->addDay()->toDateString()])
        ->assertJsonValidationErrorFor('birthday');

    $this->patchJson("/api/v1/children/{$child->id}", ['gender' => 'unicorn'])
        ->assertJsonValidationErrorFor('gender');

    expect($child->fresh()->birthday->isPast())->toBeTrue();
});

it('refuses a second parent the right to rename or delete the child', function () {
    [, $child] = family();
    $editor = editor($child);

    $this->actingAs($editor, 'sanctum')
        ->patchJson("/api/v1/children/{$child->id}", ['name' => 'Renamed'])
        ->assertForbidden();

    $this->actingAs($editor, 'sanctum')
        ->deleteJson("/api/v1/children/{$child->id}")
        ->assertForbidden();

    expect($child->fresh()->name)->toBe('Liza');
});

it('lets the creator delete a child', function () {
    [, $child] = family();

    $this->deleteJson("/api/v1/children/{$child->id}")->assertNoContent();

    $this->assertDatabaseMissing('children', ['id' => $child->id]);
});

it('takes the whole journey with it when a child is deleted', function () {
    [, $child] = family();
    $milestone = $child->milestones()->whereNotNull('child_chapter_id')->first();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
        'child_milestone_id' => $milestone->id,
    ])->assertCreated();

    $this->deleteJson("/api/v1/children/{$child->id}")->assertNoContent();

    foreach (['child_chapters', 'child_milestones', 'child_entries', 'child_members'] as $table) {
        expect(DB::table($table)->where('child_id', $child->id)->count())->toBe(0);
    }

    expect(DB::table('child_milestone_properties')->where('child_milestone_id', $milestone->id)->count())->toBe(0);
});

it('stores a photo and serves it back on the child', function () {
    Storage::fake('public');
    [, $child] = family();

    $response = $this->postJson("/api/v1/children/{$child->id}/photo", [
        'photo' => UploadedFile::fake()->image('liza.jpg'),
    ])->assertOk();

    expect($response->json('data.photo.url'))->toBeString()
        ->and($response->json('data.photo.thumb'))->toBeString()
        ->and($child->fresh()->getMedia(Child::PHOTO))->toHaveCount(1);
});

it('keeps only the newest photo', function () {
    Storage::fake('public');
    [, $child] = family();

    foreach (['first.jpg', 'second.jpg'] as $name) {
        $this->postJson("/api/v1/children/{$child->id}/photo", [
            'photo' => UploadedFile::fake()->image($name),
        ])->assertOk();
    }

    expect($child->fresh()->getMedia(Child::PHOTO))->toHaveCount(1)
        ->and($child->fresh()->getFirstMedia(Child::PHOTO)->file_name)->toBe('second.jpg');
});

it('refuses a photo that is not an image', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/photo", [
        'photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
    ])->assertJsonValidationErrorFor('photo');

    expect($child->fresh()->getMedia(Child::PHOTO))->toHaveCount(0);
});

it('refuses a photo larger than the cap', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/photo", [
        'photo' => UploadedFile::fake()->image('huge.jpg')->size(20481),
    ])->assertJsonValidationErrorFor('photo');

    expect($child->fresh()->getMedia(Child::PHOTO))->toHaveCount(0);
});

it('refuses a photo from a second parent who does not own the child', function () {
    Storage::fake('public');
    [, $child] = family();

    $this->actingAs(editor($child), 'sanctum')
        ->postJson("/api/v1/children/{$child->id}/photo", [
            'photo' => UploadedFile::fake()->image('liza.jpg'),
        ])
        ->assertForbidden();

    expect($child->fresh()->getMedia(Child::PHOTO))->toHaveCount(0);
});
