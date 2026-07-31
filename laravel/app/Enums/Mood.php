<?php

declare(strict_types=1);

namespace App\Enums;

enum Mood: string
{
    case Joyful = 'joyful';
    case Proud = 'proud';
    case Funny = 'funny';
    case Tender = 'tender';
    case Bittersweet = 'bittersweet';
}
