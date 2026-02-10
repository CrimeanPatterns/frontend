<?php

namespace AwardWallet\Engine\allaccess;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return
            strpos($question, "A new 6-digit code has been sent to your email address at") === 0
            || strpos($question, "A 6-digit code was sent to the email address associated with your account") === 0
        ;
    }

    public static function getHoldsSession(): bool
    {
        return false;
    }
}
