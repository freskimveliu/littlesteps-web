<?php

declare(strict_types=1);

use App\Enums\Mood;
use App\Models\Child;
use App\Models\User;

function memoriesDated(Child $child, array $dates): void
{
    foreach ($dates as $date) {
        $child->entries()->create([
            'description' => "Written for {$date}.",
            'date' => $date,
            'mood' => Mood::Joyful,
            'created_by_user_id' => $child->created_by_user_id,
        ]);
    }
}

it('hands back the newest memory first', function () {
    [, $child] = family();
    memoriesDated($child, [
        now()->subDays(5)->toDateString(),
        now()->toDateString(),
        now()->subDays(2)->toDateString(),
    ]);

    $dates = collect($this->getJson("/api/v1/children/{$child->id}/entries")->assertOk()->json('data.items'))
        ->pluck('date');

    expect($dates->all())->toBe($dates->sortDesc()->values()->all());
});

it('counts the whole album in the meta, not just the page', function () {
    [, $child] = family();
    memoriesDated($child, collect(range(1, 8))->map(fn (int $i) => now()->subDays($i)->toDateString())->all());

    $this->getJson("/api/v1/children/{$child->id}/entries?per_page=3")
        ->assertOk()
        ->assertJsonCount(3, 'data.items')
        ->assertJsonPath('data.meta.page', 1)
        ->assertJsonPath('data.meta.perPage', 3)
        ->assertJsonPath('data.meta.total', 8)
        ->assertJsonPath('data.meta.lastPage', 3);
});

it('walks on to the next page', function () {
    [, $child] = family();
    memoriesDated($child, collect(range(1, 8))->map(fn (int $i) => now()->subDays($i)->toDateString())->all());

    $first = $this->getJson("/api/v1/children/{$child->id}/entries?per_page=3")->json('data.items');
    $second = $this->getJson("/api/v1/children/{$child->id}/entries?per_page=3&page=2")
        ->assertOk()
        ->assertJsonPath('data.meta.page', 2)
        ->json('data.items');

    expect(collect($first)->pluck('id')->intersect(collect($second)->pluck('id')))->toBeEmpty();
});

it('shows a grandparent the album but nobody outside the family', function () {
    [, $child] = family();
    memoriesDated($child, [now()->toDateString()]);

    $this->actingAs(viewer($child), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/entries")
        ->assertOk()
        ->assertJsonCount(1, 'data.items');

    $this->actingAs(User::factory()->create(), 'sanctum')
        ->getJson("/api/v1/children/{$child->id}/entries")
        ->assertForbidden();
});

it('has an empty album before anything is written', function () {
    [, $child] = family();

    $this->getJson("/api/v1/children/{$child->id}/entries")
        ->assertOk()
        ->assertJsonCount(0, 'data.items')
        ->assertJsonPath('data.meta.total', 0);
});
