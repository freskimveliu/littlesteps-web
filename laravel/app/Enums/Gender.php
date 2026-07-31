<?php

declare(strict_types=1);

namespace App\Enums;

enum Gender: string
{
    case Boy = 'boy';
    case Girl = 'girl';
    case Other = 'other';
    case PreferNotToSay = 'prefer-not-to-say';
}
