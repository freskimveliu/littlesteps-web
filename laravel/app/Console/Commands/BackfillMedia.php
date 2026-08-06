<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ShrinkStoredPhoto;
use App\Models\ChildEntry;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class BackfillMedia extends Command
{
    protected $signature = 'media:backfill {--dry-run : Say what would be removed without removing it}';

    protected $description = 'Bring photos stored under the old rules into line: shrink them, measure them, and drop the conversions we no longer keep';

    public function handle(): int
    {
        if ($this->option('dry-run')) {
            $this->components->warn('Nothing will be changed.');

            return $this->clean(dryRun: true);
        }

        $queued = 0;

        Media::query()
            ->where('model_type', ChildEntry::class)
            ->where('collection_name', ChildEntry::MEDIA)
            ->orderBy('id')
            ->chunkById(100, function (Collection $photos) use (&$queued): void {
                foreach ($photos as $photo) {
                    ShrinkStoredPhoto::dispatch($photo->id);
                    $queued++;
                }
            });

        $this->components->info("Queued {$queued} photo(s) to be shrunk and measured.");

        $this->call('media-library:regenerate', [
            'modelType' => ChildEntry::class,
            '--only-missing' => true,
            '--force' => true,
        ]);

        return $this->clean(dryRun: false);
    }

    private function clean(bool $dryRun): int
    {
        $this->call('media-library:clean', array_filter([
            'modelType' => ChildEntry::class,
            '--force' => true,
            '--dry-run' => $dryRun,
        ]));

        return self::SUCCESS;
    }
}
