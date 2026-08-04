<?php

declare(strict_types=1);

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Chapter;
use App\Models\Level;
use App\Models\Milestone;
use App\Models\Prompt;
use App\Models\Trophy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;

/**
 * Loads database/data/*.json into the catalogue.
 *
 * This is real data the app cannot run without, so a migration applies it —
 * every environment gets it from `migrate` alone, with no seeding step.
 *
 * Matched on the id carried in the JSON, so a re-run updates each row in place
 * rather than inserting a second copy, and a child's template_*_id keeps
 * pointing at the same catalogue row it was provisioned from.
 *
 * forceFill rather than updateOrCreate: id is not fillable, so fill() would
 * drop it and let auto-increment pick a different one, quietly breaking every
 * reference the JSON makes between a milestone, its chapter and its category.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->rows('categories', new Category);
        $this->rows('chapters', new Chapter);

        foreach ($this->read('milestones') as $row) {
            $milestone = $this->put(new Milestone, collect($row)->except('properties')->all());

            // Properties are positional, not keyed, so the set is replaced wholesale.
            $milestone->properties()->delete();

            foreach ($row['properties'] as $property) {
                $milestone->properties()->create($property);
            }
        }

        $this->rows('levels', new Level);
        $this->rows('trophies', new Trophy);
        $this->rows('prompts', new Prompt);

        foreach ($this->read('app-settings') as $row) {
            AppSetting::firstOrCreate(['key' => $row['key']], ['value' => $row['value']]);
        }
    }

    public function down(): void
    {
        // Ordered so nothing is removed while a row still points at it.
        Prompt::query()->delete();
        Trophy::query()->delete();
        Level::query()->delete();
        Milestone::query()->delete();
        Chapter::query()->delete();
        Category::query()->delete();
        AppSetting::query()->delete();
    }

    private function rows(string $file, Model $model): void
    {
        foreach ($this->read($file) as $row) {
            $this->put($model, $row);
        }
    }

    /** @param array<string, mixed> $row */
    private function put(Model $model, array $row): Model
    {
        $record = $model::query()->find($row['id']) ?? $model->newInstance();

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
};
