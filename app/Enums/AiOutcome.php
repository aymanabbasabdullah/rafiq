<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How one AI request ended (AI-Architecture §3). Only `answered` shows model-generated text;
 * every other outcome shows an approved, fixed reply.
 */
enum AiOutcome: string
{
    case Answered = 'answered';
    case BlockedRedFlag = 'blocked_redflag';
    case BlockedPrefilter = 'blocked_prefilter';
    case RefusedOutOfScope = 'refused_out_of_scope';
    case RefusedPersonalCase = 'refused_personal_case';
    case BlockedPostfilter = 'blocked_postfilter';
    case Error = 'error';
    case BudgetExceeded = 'budget_exceeded';

    /**
     * The kind of reply the interface shows (emergency replies look different — M09 §7).
     */
    public function replyKind(): string
    {
        return match ($this) {
            self::Answered => 'answer',
            self::BlockedRedFlag => 'emergency',
            self::BlockedPrefilter, self::RefusedPersonalCase => 'redirect',
            self::RefusedOutOfScope => 'refusal',
            self::BlockedPostfilter, self::Error, self::BudgetExceeded => 'unavailable',
        };
    }
}
