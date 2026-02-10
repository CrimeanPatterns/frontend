<?php

namespace AwardWallet\Engine\redcard;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return str_starts_with($question, "We sent a temporary six-digit passcode to");
    }

    public static function getHoldsSession(): bool
    {
        return true;
    }
}
