<?php

declare(strict_types=1);

namespace App\Enums;

enum Relation: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Brother = 'brother';
    case Sister = 'sister';
    case Grandparent = 'grandparent';
    case AuntUncle = 'aunt-uncle';
    case Other = 'other';
}
