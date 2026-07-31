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
 * Matched on slug so re-running updates in place: an admin's later edits to a
 * row survive, and only the columns the JSON actually names get rewritten.
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
            Category::updateOrCreate(['slug' => $row['slug']], $row);
        }

        $this->command?->info('Categories: '.Category::count());
    }

    private function milestonesAndSteps(): void
    {
        foreach ($this->read('milestones') as $row) {
            TemplateMilestone::updateOrCreate(['slug' => $row['slug']], $row);
        }

        $milestones = TemplateMilestone::pluck('id', 'slug');
        $categories = Category::pluck('id', 'slug');

        foreach ($this->read('steps') as $row) {
            $step = TemplateStep::updateOrCreate(['slug' => $row['slug']], [
                'template_milestone_id' => $milestones[$row['milestone']],
                'category_id' => $categories[$row['category']],
                'name' => $row['name'],
                'description' => $row['description'],
                'icon' => $row['icon'],
                'months_from' => $row['months_from'],
                'xp' => $row['xp'],
                'sort_order' => $row['sort_order'],
            ]);

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
            TemplateLevel::updateOrCreate(['min_xp' => $row['min_xp']], $row);
        }

        $this->command?->info('Levels: '.TemplateLevel::count());
    }

    private function achievements(): void
    {
        foreach ($this->read('achievements') as $row) {
            TemplateAchievement::updateOrCreate(['slug' => $row['slug']], $row);
        }

        $this->command?->info(
            'Badges: '.TemplateAchievement::count().
            ' ('.TemplateAchievement::whereNotNull('reward')->count().' carry a gift)'
        );
    }

    private function prompts(): void
    {
        $categories = Category::pluck('id', 'slug');

        TemplatePrompt::query()->delete();

        foreach ($this->read('prompts') as $row) {
            TemplatePrompt::create([
                'category_id' => $categories[$row['category']] ?? null,
                'name' => $row['name'],
                'icon' => $row['icon'],
                'months_from' => $row['months_from'],
                'months_to' => $row['months_to'],
                'sort_order' => $row['sort_order'],
            ]);
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
