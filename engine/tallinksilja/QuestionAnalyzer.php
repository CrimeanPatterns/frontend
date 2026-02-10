<?php

namespace AwardWallet\Engine\tallinksilja;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return
            strpos($question, "A code has been sent to this e-mail") === 0
        ;
    }

    public static function getHoldsSession(): bool
    {
        return false;
    }
}
