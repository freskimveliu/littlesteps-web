<?php

declare(strict_types=1);

namespace App\Enums;

enum Relation: string
{
    case Mother = 'mother';
    case Father = 'father';
    case Grandparent = 'grandparent';
    case AuntUncle = 'aunt-uncle';
    case Sibling = 'sibling';
    case Other = 'other';
}
