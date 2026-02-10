<?php

namespace AwardWallet\MainBundle\Updater\Event;

class QuestionEvent extends AbstractEvent implements LoggableEventContextInterface
{
    public $question;

    public $displayName;

    public function __construct($accountId, $question, $displayName)
    {
        parent::__construct($accountId, 'question');
        $this->question = $question;
        $this->displayName = $displayName;
    }

    public function getLogContext(): array
    {
        return [
            'security_question' => $this->question,
        ];
    }
}
