<?php

declare(strict_types=1);

use App\Enums\AppSettingKey;
use App\Enums\PropertyKey;
use App\Models\AppSetting;
use App\Models\Category;
use App\Models\TemplateAchievement;
use App\Models\TemplateLevel;
use App\Models\TemplateMilestone;
use App\Models\TemplateStep;

beforeEach(fn () => seedCatalogue());

it('seeds the whole catalogue', function () {
    expect(Category::count())->toBe(8)
        ->and(TemplateMilestone::count())->toBe(8)
        ->and(TemplateStep::count())->toBe(118)
        ->and(TemplateLevel::count())->toBe(14)
        ->and(AppSetting::count())->toBe(5);
});

it('is idempotent', function () {
    seedCatalogue();

    expect(TemplateStep::count())->toBe(118)
        ->and(TemplateStep::withTrashed()->count())->toBe(118);
});

it('names chapters like a parent would, not like a date range', function () {
    expect(TemplateMilestone::pluck('name')->all())->toBe([
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

it('gives every chapter enough steps to be completable', function () {
    $minimum = AppSetting::number(AppSettingKey::MinStepsToCompleteMilestone);

    TemplateMilestone::withCount('steps')->get()->each(
        fn ($chapter) => expect($chapter->steps_count)
            ->toBeGreaterThanOrEqual($minimum, "{$chapter->name} has too few steps to ever be finished")
    );
});

it('orders age-anchored steps by age, so Month 2 never follows Month 3', function () {
    $months = TemplateStep::query()
        ->whereIn('name', ['Month 1', 'Month 2', 'Month 3'])
        ->orderBy('sort_order')
        ->pluck('name')
        ->all();

    expect($months)->toBe(['Month 1', 'Month 2', 'Month 3']);
});

it('keeps every step ordered by months_from within its chapter', function () {
    TemplateMilestone::with(['steps' => fn ($q) => $q->orderBy('sort_order')])
        ->get()
        ->each(function ($chapter) {
            $ages = $chapter->steps->pluck('months_from')->all();

            expect($ages)->toBe(collect($ages)->sort()->values()->all(), "{$chapter->name} is out of order");
        });
});

it('maps measurements onto chartable keys and everything else onto custom', function () {
    $birth = TemplateStep::where('name', 'Birth Day')->with('properties')->first();

    expect($birth->properties->pluck('key')->all())->toBe([
        PropertyKey::Time, PropertyKey::Length, PropertyKey::Weight, PropertyKey::Custom,
    ])->and($birth->properties->last()->name)->toBe('Place');

    $shoes = TemplateStep::where('name', 'First Pair of Shoes')->with('properties')->first();

    expect($shoes->properties->pluck('key')->unique()->all())->toBe([PropertyKey::Custom])
        ->and($shoes->properties->pluck('name')->all())->toBe(['Shoe Size', 'Brand or Style']);
});

it('paces the badge ladder so nothing is a raw entry count', function () {
    $metrics = TemplateAchievement::pluck('metric')->map->value->unique();

    expect($metrics)->not->toContain('entries')
        ->and(TemplateAchievement::count())->toBe(32)
        ->and(TemplateAchievement::whereNotNull('reward')->count())->toBe(8);
});

it('exposes the catalogue to the app', function () {
    [$user] = family();

    $this->getJson('/api/v1/catalogue')
        ->assertOk()
        ->assertJsonPath('data.limits.daily_free_entries', 1)
        ->assertJsonPath('data.limits.free_entry_xp', 10)
        ->assertJsonPath('data.limits.daily_step_entries', 5)
        ->assertJsonCount(8, 'data.categories')
        ->assertJsonCount(14, 'data.levels');
});
