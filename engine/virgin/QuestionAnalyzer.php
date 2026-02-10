<?php

namespace AwardWallet\Engine\virgin;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return stripos($question, "OTP generated successfully") !== false
            || stripos($question, "sent you a code to verify your details") !== false;
    }

    public static function getHoldsSession(): bool
    {
        return true;
    }
}
