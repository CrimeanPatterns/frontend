<?php

namespace AwardWallet\Engine\bilt;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return stristr($question, "code we sent to ");
    }

    public static function getHoldsSession(): bool
    {
        return true;
    }
}
