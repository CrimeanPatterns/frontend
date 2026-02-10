<?php

namespace AwardWallet\Engine\thaiair;

use AwardWallet\Common\OneTimeCode\EmailQuestionAnalyzerInterface;

class QuestionAnalyzer implements EmailQuestionAnalyzerInterface
{
    public static function isOtcQuestion(string $question): bool
    {
        return str_starts_with($question, "We have sent a 4-digit OTP code to your registered email address");
    }

    public static function getHoldsSession(): bool
    {
        return true;
    }
}
