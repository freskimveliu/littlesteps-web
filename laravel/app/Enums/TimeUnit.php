<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonImmutable;

enum TimeUnit: string
{
    case Days = 'days';
    case Months = 'months';

    public function after(CarbonImmutable $from, int $amount): CarbonImmutable
    {
        return match ($this) {
            self::Days => $from->addDays($amount),
            self::Months => $from->addMonths($amount),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Days => 'days',
            self::Months => 'months',
        };
    }
}
