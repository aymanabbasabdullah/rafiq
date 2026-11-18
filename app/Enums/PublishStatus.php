<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Publication status for content translations (ADM-010).
 */
enum PublishStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Hidden = 'hidden';
    case Archived = 'archived';
}
