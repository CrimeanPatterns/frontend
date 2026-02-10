<?php

namespace AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion;

class Question
{
    /**
     * @var string|null
     */
    protected $question;
    /**
     * @var string|null
     */
    protected $answer;

    public function __construct(?string $question, ?string $answer)
    {
        $this->question = $question;
        $this->answer = $answer;
    }

    public function getQuestion()
    {
        return $this->question;
    }

    public function getAnswer()
    {
        return $this->answer;
    }
}
