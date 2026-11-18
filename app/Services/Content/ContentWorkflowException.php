<?php

declare(strict_types=1);

namespace App\Services\Content;

use DomainException;

/**
 * A content workflow transition that is not allowed. The message is a translation key.
 */
class ContentWorkflowException extends DomainException
{
    public function __construct(public readonly string $translationKey)
    {
        parent::__construct($translationKey);
    }
}
