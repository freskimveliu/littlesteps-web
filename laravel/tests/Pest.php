<?php

declare(strict_types=1);

use App\Actions\Children\CreateChild;
use App\Data\ChildData;
use App\Enums\AppSettingKey;
use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\Relation;
use App\Models\AppSetting;
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

/**
 * Change one of the numbers that pace the app.
 *
 * Written through the model rather than the query builder, because App\Support\Settings
 * holds them for the request and only a model write tells it to let go — the same
 * path the console takes.
 */
function setting(AppSettingKey $key, int $value): void
{
    AppSetting::updateOrCreate(['key' => $key->value], ['value' => (string) $value]);
}

/**
 * Sign in at the console. Called again after family(), which switches the
 * acting guard to the app's token and would otherwise leave us at the door.
 */
function console(): User
{
    $admin = User::query()->where('is_admin', true)->first()
        ?? User::factory()->create(['is_admin' => true]);

    test()->actingAs($admin);

    return $admin;
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
