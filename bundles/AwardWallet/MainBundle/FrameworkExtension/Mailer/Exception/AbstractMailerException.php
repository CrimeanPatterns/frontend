<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Exception;

abstract class AbstractMailerException extends \RuntimeException implements MailerExceptionInterface
{
    /**
     * @var \Swift_Message
     */
    protected $target;

    public function __construct(\Swift_Message $target, string $message = '', $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->target = $target;
    }

    public function getTarget(): \Swift_Message
    {
        return $this->target;
    }
}
