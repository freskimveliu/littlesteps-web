<?php

declare(strict_types=1);

use App\Enums\Gender;
use App\Models\Child;
use App\Models\ChildChapter;
use App\Models\ChildMilestone;
use Inertia\Testing\AssertableInertia;

beforeEach(fn () => test()->withoutVite());

it('offers the console the genders a child can be corrected to', function () {
    family();

    console();

    $this->get('/admin/children')
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Children/Index')
            ->where('genders', array_column(Gender::cases(), 'value'))
            ->etc()
        );
});

it('corrects a child from the console', function () {
    [, $child] = family();

    console();

    $this->put("/admin/children/{$child->id}", [
            'name' => 'Elena',
            'birthday' => '2024-06-01',
            'gender' => Gender::Boy->value,
        ])
        ->assertRedirect();

    $child->refresh();

    expect($child->name)->toBe('Elena')
        ->and($child->birthday->toDateString())->toBe('2024-06-01')
        ->and($child->gender)->toBe(Gender::Boy);
});

it('refuses a birthday that has not happened yet', function () {
    [, $child] = family();

    console();

    $this->put("/admin/children/{$child->id}", [
            'name' => $child->name,
            'birthday' => now()->addDay()->toDateString(),
            'gender' => $child->gender->value,
        ])
        ->assertSessionHasErrors('birthday');

    expect($child->fresh()->name)->toBe($child->name);
});

it('takes the whole journey with the child on delete', function () {
    [, $child] = family();

    expect(ChildChapter::where('child_id', $child->id)->exists())->toBeTrue();

    console();

    $this->delete("/admin/children/{$child->id}")
        ->assertRedirect();

    expect(Child::find($child->id))->toBeNull()
        ->and(ChildChapter::where('child_id', $child->id)->exists())->toBeFalse()
        ->and(ChildMilestone::where('child_id', $child->id)->exists())->toBeFalse();
});

it('keeps a parent out of the console routes that change a child', function () {
    [$parent, $child] = family();

    $this->actingAs($parent)
        ->put("/admin/children/{$child->id}", [
            'name' => 'Renamed',
            'birthday' => '2024-06-01',
            'gender' => Gender::Boy->value,
        ])
        ->assertForbidden();

    $this->actingAs($parent)
        ->delete("/admin/children/{$child->id}")
        ->assertForbidden();

    expect(Child::find($child->id)?->name)->toBe($child->name);
});
