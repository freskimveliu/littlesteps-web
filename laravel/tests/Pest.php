<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\Relation;
use App\Models\Child;
use App\Models\User;
use Database\Seeders\CatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
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

    $child = app(App\Actions\Children\CreateChild::class)->handle(
        $user,
        new App\Data\ChildData(
            name: 'Liza',
            birthday: now()->subMonths($ageMonths)->toDateString(),
            gender: Gender::Girl,
            relation: Relation::Mother,
        ),
    );

    test()->actingAs($user, 'sanctum');

    return [$user, $child];
}

function seedCatalogue(): void
{
    test()->seed(CatalogueSeeder::class);
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
