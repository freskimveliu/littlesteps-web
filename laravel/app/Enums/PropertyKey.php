<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * What a step can ask a parent to measure.
 *
 * The known keys are what the growth chart reads, so they carry a fixed unit
 * and the app owns their wording. Custom is the escape hatch: the parent names
 * it themselves ("Shoe size"), and nothing tries to plot it.
 */
enum PropertyKey: string
{
    case Weight = 'weight';
    case Length = 'length';
    case Time = 'time';
    case Custom = 'custom';

    public function unit(): ?string
    {
        return match ($this) {
            self::Weight => 'kg',
            self::Length => 'cm',
            self::Time, self::Custom => null,
        };
    }

    public function isChartable(): bool
    {
        return in_array($this, [self::Weight, self::Length], true);
    }

    public function needsName(): bool
    {
        return $this === self::Custom;
    }
}
