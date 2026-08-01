<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    test()->withoutVite();
    test()->actingAs(User::factory()->create(['is_admin' => true, 'name' => 'The first admin']));
});

it('creates an account from the console with its password already hashed', function () {
    $this->post('/admin/users', [
        'name' => 'Freskim',
        'email' => 'new@example.com',
        'password' => 'a-long-enough-password',
        'timezone' => 'Europe/Tirane',
        'is_admin' => true,
    ])->assertRedirect();

    $user = User::where('email', 'new@example.com')->firstOrFail();

    expect($user->is_admin)->toBeTrue()
        ->and($user->password)->not->toBe('a-long-enough-password')
        ->and(Hash::check('a-long-enough-password', $user->password))->toBeTrue();
});

it('refuses an account on an email already in use', function () {
    $taken = User::factory()->create(['email' => 'taken@example.com']);

    $this->post('/admin/users', [
        'name' => 'Second try',
        'email' => $taken->email,
        'password' => 'a-long-enough-password',
        'timezone' => 'UTC',
    ])->assertSessionHasErrors('email');

    expect(User::where('email', 'taken@example.com')->count())->toBe(1);
});

it('refuses a password too short to protect anything', function () {
    $this->post('/admin/users', [
        'name' => 'Freskim',
        'email' => 'new@example.com',
        'password' => 'abc',
        'timezone' => 'UTC',
    ])->assertSessionHasErrors('password');

    expect(User::where('email', 'new@example.com')->exists())->toBeFalse();
});

it('will not let the last admin lock everyone out', function () {
    $onlyAdmin = User::where('is_admin', true)->firstOrFail();

    $this->put("/admin/users/{$onlyAdmin->id}", [
        'name' => $onlyAdmin->name,
        'email' => $onlyAdmin->email,
        'timezone' => 'UTC',
        'is_admin' => false,
    ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($onlyAdmin->fresh()->is_admin)->toBeTrue();
});

it('lets an admin step down once somebody else can take over', function () {
    $stepping = User::where('is_admin', true)->firstOrFail();
    User::factory()->create(['is_admin' => true]);

    $this->put("/admin/users/{$stepping->id}", [
        'name' => $stepping->name,
        'email' => $stepping->email,
        'timezone' => 'UTC',
        'is_admin' => false,
    ])->assertRedirect();

    expect($stepping->fresh()->is_admin)->toBeFalse();
});

it('brings back an account deleted inside its grace period, children and all', function () {
    [$user, $child] = family();
    $user->delete();

    expect(User::find($user->id))->toBeNull();

    $this->actingAs(User::where('is_admin', true)->firstOrFail())
        ->post("/admin/users/{$user->id}/restore")
        ->assertRedirect();

    expect(User::find($user->id))->not->toBeNull()
        ->and($child->fresh())->not->toBeNull()
        ->and($child->fresh()->chapters()->count())->toBeGreaterThan(0);
});

it('has nothing to restore for an account that was never deleted', function () {
    $user = User::factory()->create();

    $this->post("/admin/users/{$user->id}/restore")->assertNotFound();
});

it('reaches a deleted account to edit it', function () {
    $user = User::factory()->create(['name' => 'Gone']);
    $user->delete();

    $this->put("/admin/users/{$user->id}", [
        'name' => 'Gone but corrected',
        'email' => $user->email,
        'timezone' => 'UTC',
    ])->assertRedirect();

    expect(User::withTrashed()->find($user->id)->name)->toBe('Gone but corrected');
});
