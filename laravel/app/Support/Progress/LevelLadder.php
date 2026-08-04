<?php

declare(strict_types=1);

namespace App\Support\Progress;

use App\Models\Level;
use Illuminate\Support\Collection;

class LevelLadder
{
    /** @var Collection<int, Level>|null */
    private static ?Collection $ladder = null;

    /** @return Collection<int, Level> */
    public static function ladder(): Collection
    {
        return self::$ladder ??= Level::query()
            ->where('is_active', true)
            ->orderBy('min_xp')
            ->get();
    }

    /**
     * Keyed in camelCase: this goes out in a payload, and the columns it is read
     * from are not the app's vocabulary.
     *
     * @return array{level: int, name: string, icon: string, minXp: int, next: ?array{level: int, name: string, minXp: int}, xpToNext: ?int, progress: float}
     */
    public static function for(int $xp): array
    {
        $ladder = self::ladder();
        $index = 0;

        foreach ($ladder as $i => $level) {
            if ($xp >= $level->min_xp) {
                $index = $i;
            }
        }

        $current = $ladder[$index];
        $next = $ladder[$index + 1] ?? null;

        $span = $next ? $next->min_xp - $current->min_xp : 0;
        $into = $xp - $current->min_xp;

        return [
            'level' => $index + 1,
            'name' => $current->name,
            'icon' => $current->icon->value,
            'minXp' => $current->min_xp,
            'next' => $next ? [
                'level' => $index + 2,
                'name' => $next->name,
                'minXp' => $next->min_xp,
            ] : null,
            'xpToNext' => $next ? $next->min_xp - $xp : null,
            'progress' => $span > 0 ? round($into / $span, 4) : 1.0,
        ];
    }

    public static function total(): int
    {
        return self::ladder()->count();
    }
}
