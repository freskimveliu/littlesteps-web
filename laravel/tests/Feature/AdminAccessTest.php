<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Models\Child;
use App\Models\Level;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
            ->where('children.data.0.level_name', Level::query()->orderBy('min_xp')->firstOrFail()->name)
        );
});

it('opens the children list on the newest arrivals', function () {
    [, $child] = family();
    Child::factory()->create(['name' => 'Added last year', 'created_at' => now()->subYear()]);

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get('/admin/children')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Children/Index')
            ->where('filters.sort', 'created_at')
            ->where('filters.order', 'desc')
            ->where('children.data.0.name', $child->name)
            ->etc()
        );
});

it('gives every part of one child its own page, without letting an admin edit it', function (string $suffix, string $component) {
    [, $child] = family();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get("/admin/children/{$child->id}{$suffix}")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component($component)
            ->where('summary.child.name', $child->name)
            ->has('summary.metrics')
            ->etc()
        );
})->with([
    'journey' => ['', 'Admin/Children/Show/Journey'],
    'memories' => ['/memories', 'Admin/Children/Show/Memories'],
    'trophies' => ['/trophies', 'Admin/Children/Show/Trophies'],
    'gifts' => ['/gifts', 'Admin/Children/Show/Gifts'],
    'family' => ['/family', 'Admin/Children/Show/Family'],
]);

it('hands the admin the whole of a memory, photos and all', function () {
    Storage::fake('public');
    [$parent, $child] = family();

    $this->postJson("/api/v1/children/{$child->id}/entries", [
        'description' => 'She laughed at the cat.',
        'date' => now()->toDateString(),
        'mood' => Mood::Joyful->value,
        'media' => [UploadedFile::fake()->image('first-smile.jpg')],
    ])->assertCreated();

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get("/admin/children/{$child->id}/memories")
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Children/Show/Memories')
            ->has('entries.0.media', 1)
            ->where('entries.0.description', 'She laughed at the cat.')
            ->where('entries.0.author.name', $parent->name)
            ->has('entries.0.media.0.thumb')
            ->has('entries.0.media.0.display')
            ->has('entries.0.media.0.original')
            ->etc()
        );
});

it('lets somebody who is not an admin sign out without hitting the gate', function () {
    $user = User::factory()->create(['is_admin' => false]);

    $this->actingAs($user)->post('/admin/logout')->assertRedirect();

    $this->assertGuest();
});
