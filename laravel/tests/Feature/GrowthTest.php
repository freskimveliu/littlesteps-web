<?php

declare(strict_types=1);

use App\Enums\PropertyKey;

beforeEach(fn () => seedCatalogue());

it('records measurements against a step and charts them by age', function () {
    [, $child] = family(ageMonths: 12);

    foreach ([1, 2] as $month) {
        $step = $child->steps()->where('name', "Month {$month}")->first();

        $child->entries()->create([
            'child_step_id' => $step->id,
            'date' => $child->birthday->copy()->addMonths($month)->toDateString(),
            'created_by_user_id' => $child->created_by_user_id,
        ])->properties()->createMany([
            ['key' => PropertyKey::Weight, 'value' => (string) (4 + $month)],
            ['key' => PropertyKey::Length, 'value' => (string) (54 + $month * 3)],
        ]);
    }

    $series = collect($this->getJson("/api/v1/children/{$child->id}/growth")->assertOk()->json('data'));

    $weight = $series->firstWhere('key', 'weight');

    expect($series)->toHaveCount(2)
        ->and($weight['unit'])->toBe('kg')
        ->and($weight['points'])->toHaveCount(2)
        ->and($weight['points'][0]['ageMonths'])->toBe(1)
        ->and($weight['points'][1]['value'])->toEqual(6);
});

it('keeps custom properties out of the chart', function () {
    [, $child] = family(ageMonths: 12);

    $child->entries()->create([
        'date' => now()->toDateString(),
        'created_by_user_id' => $child->created_by_user_id,
    ])->properties()->create([
        'key' => PropertyKey::Custom,
        'name' => 'Shoe size',
        'value' => 'EU 20',
    ]);

    $this->getJson("/api/v1/children/{$child->id}/growth")->assertOk()->assertJsonCount(0, 'data');
});

it('lets a parent add their own step with a custom property', function () {
    [, $child] = family();
    $milestone = $child->milestones()->first();

    $response = $this->postJson("/api/v1/children/{$child->id}/steps", [
        'child_milestone_id' => $milestone->id,
        'name' => 'First pair of shoes',
        'properties' => [
            ['key' => 'custom', 'name' => 'Shoe size'],
        ],
    ])->assertCreated();

    expect($response->json('data.isEditable'))->toBeTrue()
        ->and($response->json('data.properties.0.name'))->toBe('Shoe size')
        ->and($response->json('data.properties.0.isChartable'))->toBeFalse();
});

it('requires a name for a custom property', function () {
    [, $child] = family();
    $milestone = $child->milestones()->first();

    $this->postJson("/api/v1/children/{$child->id}/steps", [
        'child_milestone_id' => $milestone->id,
        'name' => 'Something',
        'properties' => [['key' => 'custom']],
    ])->assertJsonValidationErrorFor('properties.0.name');
});

it('serves an age-appropriate prompt', function () {
    [, $child] = family(ageMonths: 2);

    $prompt = $this->getJson("/api/v1/children/{$child->id}/prompt")->assertOk()->json('data');

    expect($prompt['name'])->toBeString()->not->toBeEmpty();
});
