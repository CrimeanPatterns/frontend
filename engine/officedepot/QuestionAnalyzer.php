<?php

namespace AwardWallet\Engine\officedepot;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return
            str_starts_with($question, "A validation code was sent to")
            || stripos($question, "Enter the six digit validation code") !== false
        ;
    }

    public static function getHoldsSession(): bool
    {
        return true;
    }
}
