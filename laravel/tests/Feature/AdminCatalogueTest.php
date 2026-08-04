<?php

declare(strict_types=1);

use App\Enums\TimeUnit;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Level;
use App\Models\Milestone;
use App\Models\Trophy;

beforeEach(function () {
    test()->withoutVite();
    console();
});

function chapterPayload(array $overrides = []): array
{
    return [
        'name' => 'The Long Summer',
        'description' => 'A chapter written from the console.',
        'icon' => 'balloon-outline',
        'months_from' => 12,
        'xp' => 500,
        'sort_order' => 900,
        ...$overrides,
    ];
}

it('writes a new chapter into the catalogue', function () {
    $this->post('/admin/chapters', chapterPayload())->assertRedirect();

    $this->assertDatabaseHas('chapters', ['name' => 'The Long Summer', 'xp' => 500]);
});

it('refuses a catalogue chapter with no name, no icon or impossible xp', function () {
    $this->post('/admin/chapters', chapterPayload(['name' => null]))->assertSessionHasErrors('name');
    $this->post('/admin/chapters', chapterPayload(['icon' => null]))->assertSessionHasErrors('icon');
    $this->post('/admin/chapters', chapterPayload(['xp' => 10001]))->assertSessionHasErrors('xp');
    $this->post('/admin/chapters', chapterPayload(['months_from' => 217]))->assertSessionHasErrors('months_from');

    expect(Chapter::where('name', 'The Long Summer')->exists())->toBeFalse();
});

it('leaves a journey already under way alone when the catalogue is reworded', function () {
    [, $child] = family();
    console();

    $chapter = Chapter::query()->orderBy('sort_order')->first();
    $before = $child->chapters()->where('chapter_id', $chapter->id)->first();

    $this->put("/admin/chapters/{$chapter->id}", chapterPayload([
        'name' => 'Renamed by the console',
        'xp' => 9999,
    ]))->assertRedirect();

    $after = $child->chapters()->find($before->id);

    expect($after->name)->toBe($before->name)
        ->and($after->xp)->toBe($before->xp);
});

it('only hides a catalogue chapter so a provisioned child keeps its trail', function () {
    [, $child] = family();
    console();

    $chapter = Chapter::query()->orderBy('sort_order')->first();

    $this->delete("/admin/chapters/{$chapter->id}")->assertRedirect();

    expect(Chapter::find($chapter->id))->toBeNull()
        ->and(Chapter::withTrashed()->find($chapter->id))->not->toBeNull()
        ->and($child->chapters()->where('chapter_id', $chapter->id)->exists())->toBeTrue();
});

it('keeps a new catalogue chapter off a child who was already provisioned', function () {
    [, $child] = family();
    console();

    $before = $child->chapters()->count();

    $this->post('/admin/chapters', chapterPayload())->assertRedirect();

    expect($child->chapters()->count())->toBe($before);
});

it('refuses a catalogue milestone pointing at a chapter or category that is not there', function () {
    $payload = [
        'chapter_id' => 9999,
        'category_id' => 9999,
        'name' => 'Somewhere new',
        'xp' => 20,
        'sort_order' => 900,
    ];

    $this->post('/admin/milestones', $payload)
        ->assertSessionHasErrors(['chapter_id', 'category_id']);

    expect(Milestone::where('name', 'Somewhere new')->exists())->toBeFalse();
});

it('writes a new catalogue milestone with its measurements', function () {
    $chapter = Chapter::query()->first();
    $category = Category::query()->first();

    $this->post('/admin/milestones', [
        'chapter_id' => $chapter->id,
        'category_id' => $category->id,
        'name' => 'First swim',
        'icon' => 'water-outline',
        'happens_after' => 550,
        'happens_unit' => 'days',
        'xp' => 30,
        'sort_order' => 900,
        'properties' => [['key' => 'custom', 'name' => 'Depth']],
    ])->assertRedirect();

    $milestone = Milestone::where('name', 'First swim')->first();

    expect($milestone)->not->toBeNull()
        ->and($milestone->properties()->count())->toBe(1)
        ->and($milestone->happens_after)->toBe(550)
        ->and($milestone->happens_unit)->toBe(TimeUnit::Days);
});

it('refuses a measurement the parent would never be asked to name', function () {
    $chapter = Chapter::query()->first();
    $category = Category::query()->first();

    $this->post('/admin/milestones', [
        'chapter_id' => $chapter->id,
        'category_id' => $category->id,
        'name' => 'First swim',
        'xp' => 30,
        'sort_order' => 900,
        'properties' => [['key' => 'custom', 'name' => null]],
    ])->assertSessionHasErrors('properties.0.name');
});

it('refuses two levels sharing a rung of the ladder', function () {
    $taken = Level::query()->orderBy('min_xp')->skip(1)->first();

    $this->post('/admin/levels', [
        'name' => 'Impostor',
        'icon' => 'trophy',
        'min_xp' => $taken->min_xp,
    ])->assertSessionHasErrors('min_xp');

    expect(Level::where('name', 'Impostor')->exists())->toBeFalse();
});

it('refuses to remove the level every child starts on', function () {
    $first = Level::query()->where('min_xp', 0)->firstOrFail();

    $this->delete("/admin/levels/{$first->id}")
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Level::find($first->id))->not->toBeNull();
});

it('removes a level further up the ladder', function () {
    $top = Level::query()->orderByDesc('min_xp')->first();

    $this->delete("/admin/levels/{$top->id}")->assertRedirect();

    expect(Level::find($top->id))->toBeNull();
});

it('refuses a trophy judged on something nothing measures', function () {
    $this->post('/admin/trophies', [
        'name' => 'Most Determined',
        'icon' => 'trophy',
        'metric' => 'sheer-willpower',
        'threshold' => 5,
        'xp' => 100,
        'sort_order' => 900,
    ])->assertSessionHasErrors('metric');

    expect(Trophy::where('name', 'Most Determined')->exists())->toBeFalse();
});

it('refuses a prompt whose window closes before it opens', function () {
    $this->post('/admin/prompts', [
        'name' => 'What made them laugh today?',
        'months_from' => 12,
        'months_to' => 6,
        'sort_order' => 900,
    ])->assertSessionHasErrors('months_to');
});

it('refuses a category colour that is not a colour', function () {
    $this->post('/admin/categories', [
        'name' => 'Adventures',
        'icon' => 'balloon-outline',
        'color' => 'sea green',
        'sort_order' => 900,
    ])->assertSessionHasErrors('color');
});
