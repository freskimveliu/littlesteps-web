<?php

declare(strict_types=1);

namespace App\Enums;

enum MemberRole: string
{
    case Editor = 'editor';
    case Viewer = 'viewer';

    public function canEdit(): bool
    {
        return $this === self::Editor;
    }
}
