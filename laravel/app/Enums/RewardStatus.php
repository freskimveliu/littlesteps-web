<?php

declare(strict_types=1);

namespace App\Enums;

enum RewardStatus: string
{
    case Unclaimed = 'unclaimed';
    case Generating = 'generating';
    case Ready = 'ready';
    case Failed = 'failed';

    public function isClaimable(): bool
    {
        return in_array($this, [self::Unclaimed, self::Failed], true);
    }
}
