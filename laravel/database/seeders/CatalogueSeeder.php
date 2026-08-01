<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\TemplateAchievement;
use App\Models\TemplateLevel;
use App\Models\TemplateMilestone;
use App\Models\TemplatePrompt;
use App\Models\TemplateStep;
use App\Models\TemplateStepProperty;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Loads database/data/*.json into the catalogue.
 *
 * Matched on the id carried in the JSON, so re-running updates each row in
 * place rather than inserting a second copy — and a child's template_*_id
 * keeps pointing at the same catalogue row it was provisioned from.
 *
 * forceFill rather than updateOrCreate: id is not fillable, so fill() would
 * drop it and let auto-increment pick a different one, quietly breaking every
 * reference the JSON makes between a step, its chapter and its category.
 */
class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        $this->categories();
        $this->milestonesAndSteps();
        $this->levels();
        $this->achievements();
        $this->prompts();
        $this->appSettings();
    }

    /**
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  T  $model
     * @param  array<string, mixed>  $row
     * @return T
     */
    private static function put($model, array $row)
    {
        $existing = $model::query()->find($row['id']);
        $record = $existing ?? $model->newInstance();

        $record->forceFill($row)->save();

        return $record;
    }

    /** @return array<int, array<string, mixed>> */
    private function read(string $file): array
    {
        $path = database_path("data/{$file}.json");

        if (! is_file($path)) {
            throw new RuntimeException("Missing catalogue file: {$path}");
        }

        return json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function categories(): void
    {
        foreach ($this->read('categories') as $row) {
            self::put(new Category, $row);
        }

        $this->command?->info('Categories: '.Category::count());
    }

    private function milestonesAndSteps(): void
    {
        foreach ($this->read('milestones') as $row) {
            self::put(new TemplateMilestone, $row);
        }

        foreach ($this->read('steps') as $row) {
            $step = self::put(new TemplateStep, collect($row)->except('properties')->all());

            // Properties are positional, not keyed, so the set is replaced wholesale.
            $step->properties()->delete();

            foreach ($row['properties'] as $property) {
                $step->properties()->create($property);
            }
        }

        $this->command?->info(
            'Chapters: '.TemplateMilestone::count().
            ' · Steps: '.TemplateStep::count().
            ' · Properties: '.TemplateStepProperty::count()
        );
    }

    private function levels(): void
    {
        foreach ($this->read('levels') as $row) {
            self::put(new TemplateLevel, $row);
        }

        $this->command?->info('Levels: '.TemplateLevel::count());
    }

    private function achievements(): void
    {
        foreach ($this->read('achievements') as $row) {
            self::put(new TemplateAchievement, $row);
        }

        $this->command?->info(
            'Badges: '.TemplateAchievement::count().
            ' ('.TemplateAchievement::whereNotNull('reward')->count().' carry a gift)'
        );
    }

    private function prompts(): void
    {
        foreach ($this->read('prompts') as $row) {
            self::put(new TemplatePrompt, $row);
        }

        $this->command?->info('Prompts: '.TemplatePrompt::count());
    }

    private function appSettings(): void
    {
        foreach ($this->read('app-settings') as $row) {
            AppSetting::firstOrCreate(['key' => $row['key']], ['value' => $row['value']]);
        }

        $this->command?->info('Settings: '.AppSetting::count());
    }
}
