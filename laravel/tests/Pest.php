<?php

declare(strict_types=1);

use App\Actions\Children\CreateChild;
use App\Data\ChildData;
use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\Relation;
use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * A child with the full catalogue provisioned onto them, signed in as the
 * parent who created them.
 *
 * @return array{0: User, 1: Child}
 */
function family(int $ageMonths = 6): array
{
    $user = User::factory()->create(['timezone' => 'Europe/Tirane']);

    $child = app(CreateChild::class)->handle(
        $user,
        new ChildData(
            name: 'Liza',
            birthday: now()->subMonths($ageMonths)->toDateString(),
            gender: Gender::Girl,
            relation: Relation::Mother,
        ),
    );

    test()->actingAs($user, 'sanctum');

    return [$user, $child];
}

function viewer(Child $child): User
{
    $user = User::factory()->create();

    $child->memberships()->create([
        'user_id' => $user->id,
        'relation' => Relation::Grandparent,
        'role' => MemberRole::Viewer,
    ]);

    return $user;
}

/**
 * A second parent who may add memories but does not own the child — the
 * distinction ChildPolicy draws between contribute and update.
 */
function editor(Child $child): User
{
    $user = User::factory()->create();

    $child->memberships()->create([
        'user_id' => $user->id,
        'relation' => Relation::Father,
        'role' => MemberRole::Editor,
    ]);

    return $user;
}
