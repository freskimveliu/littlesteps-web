<?php

declare(strict_types=1);

use App\Models\User;
use Inertia\Testing\AssertableInertia;

beforeEach(fn () => test()->withoutVite());

/** Every screen behind the admin gate. */
dataset('admin screens', [
    '/admin',
    '/admin/children',
    '/admin/users',
    '/admin/chapters',
    '/admin/milestones',
    '/admin/trophies',
    '/admin/settings',
    '/admin/gifts',
]);

it('sends a signed-out visitor to the login page', function (string $screen) {
    $this->get($screen)->assertRedirect('/admin/login');
})->with('admin screens');

it('refuses a parent who is not an admin', function (string $screen) {
    $this->actingAs(User::factory()->create(['is_admin' => false]))
        ->get($screen)
        ->assertForbidden();
})->with('admin screens');

it('turns an ordinary parent away at the admin login', function () {
    $user = User::factory()->create(['is_admin' => false, 'password' => Hash::make('password')]);

    $this->post('/admin/login', ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('lets an admin in', function () {
    $admin = User::factory()->create(['is_admin' => true, 'password' => Hash::make('password')]);

    $this->post('/admin/login', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($admin);
});

it('shows an admin every family on the service', function () {
    [, $child] = family();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get('/admin/children')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Children/Index')
            ->has('children.data', 1)
            ->where('children.data.0.name', $child->name)
            ->where('children.data.0.level', 1)
        );
});

it('shows an admin one child without letting them edit it', function () {
    [, $child] = family();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get("/admin/children/{$child->id}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/Children/Show'));
});

it('lets somebody who is not an admin sign out without hitting the gate', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->post('/admin/logout')->assertRedirect();

    $this->assertGuest();
});
