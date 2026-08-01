<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\PropertyKey;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Level;
use App\Models\Milestone;
use App\Models\Trophy;

it('seeds the whole catalogue', function () {
    expect(Category::count())->toBe(8)
        ->and(Chapter::count())->toBe(8)
        ->and(Milestone::count())->toBe(118)
        ->and(Level::count())->toBe(14)
        ->and(AppSetting::count())->toBe(6);
});

it('is idempotent', function () {

    expect(Milestone::count())->toBe(118)
        ->and(Milestone::withTrashed()->count())->toBe(118);
});

it('names chapters like a parent would, not like a date range', function () {
    expect(Chapter::pluck('name')->all())->toBe([
        'The First Hello',
        'Waking to the World',
        'On the Move',
        'Little Explorer',
        'Words and Wonder',
        'A Mind of Their Own',
        'Making and Believing',
        'Ready for the World',
    ]);
});

it('gives every chapter enough milestones to be completable', function () {
    $minimum = AppSetting::number(AppSettingKey::MinMilestonesToCompleteChapter);

    Chapter::withCount('milestones')->get()->each(
        fn ($chapter) => expect($chapter->milestones_count)
            ->toBeGreaterThanOrEqual($minimum, "{$chapter->name} has too few milestones to ever be finished")
    );
});

it('orders age-anchored milestones by age, so Month 2 never follows Month 3', function () {
    $months = Milestone::query()
        ->whereIn('name', ['Month 1', 'Month 2', 'Month 3'])
        ->orderBy('sort_order')
        ->pluck('name')
        ->all();

    expect($months)->toBe(['Month 1', 'Month 2', 'Month 3']);
});

it('keeps every milestone ordered by months_from within its chapter', function () {
    Chapter::with(['milestones' => fn ($q) => $q->orderBy('sort_order')])
        ->get()
        ->each(function ($chapter) {
            $ages = $chapter->milestones->pluck('months_from')->all();

            expect($ages)->toBe(collect($ages)->sort()->values()->all(), "{$chapter->name} is out of order");
        });
});

it('maps measurements onto chartable keys and everything else onto custom', function () {
    $birth = Milestone::where('name', 'Birth Day')->with('properties')->first();

    expect($birth->properties->pluck('key')->all())->toBe([
        PropertyKey::Time, PropertyKey::Length, PropertyKey::Weight, PropertyKey::Custom,
    ])->and($birth->properties->last()->name)->toBe('Place');

    $shoes = Milestone::where('name', 'First Pair of Shoes')->with('properties')->first();

    expect($shoes->properties->pluck('key')->unique()->all())->toBe([PropertyKey::Custom])
        ->and($shoes->properties->pluck('name')->all())->toBe(['Shoe Size', 'Brand or Style']);
});

it('paces the trophy ladder so nothing is a raw entry count', function () {
    $metrics = Trophy::pluck('metric')->map->value->unique();

    expect($metrics)->not->toContain('entries')
        ->and(Trophy::count())->toBe(32)
        ->and(Trophy::whereNotNull('reward')->count())->toBe(8);
});

it('exposes the catalogue to the app', function () {
    [$user] = family();

    $this->getJson('/api/v1/catalogue')
        ->assertOk()
        ->assertJsonPath('data.limits.daily_free_entries', 1)
        ->assertJsonPath('data.limits.free_entry_xp', 10)
        ->assertJsonPath('data.limits.daily_milestone_entries', 5)
        ->assertJsonCount(8, 'data.categories')
        ->assertJsonCount(14, 'data.levels');
});
