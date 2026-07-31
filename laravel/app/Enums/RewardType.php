<?php

declare(strict_types=1);

namespace App\Enums;

enum RewardType: string
{
    case Story = 'story';
    case Image = 'image';
    case Book = 'book';
}
