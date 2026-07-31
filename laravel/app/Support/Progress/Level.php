<?php

declare(strict_types=1);

namespace App\Support\Progress;

use App\Models\TemplateLevel;
use Illuminate\Support\Collection;

class Level
{
    /** @var Collection<int, TemplateLevel>|null */
    private static ?Collection $ladder = null;

    /** @return Collection<int, TemplateLevel> */
    public static function ladder(): Collection
    {
        return self::$ladder ??= TemplateLevel::query()
            ->where('is_active', true)
            ->orderBy('min_xp')
            ->get();
    }

    /** @return array{level: int, name: string, icon: string, min_xp: int, next: ?array{level: int, name: string, min_xp: int}, xp_to_next: ?int, progress: float} */
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
            'min_xp' => $current->min_xp,
            'next' => $next ? [
                'level' => $index + 2,
                'name' => $next->name,
                'min_xp' => $next->min_xp,
            ] : null,
            'xp_to_next' => $next ? $next->min_xp - $xp : null,
            'progress' => $span > 0 ? round($into / $span, 4) : 1.0,
        ];
    }

    public static function total(): int
    {
        return self::ladder()->count();
    }
}
