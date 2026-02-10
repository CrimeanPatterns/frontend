<?php

namespace AwardWallet\MainBundle\Security;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

class TranslatedAuthenticationException extends AuthenticationException
{
    /**
     * @var string
     */
    private $protection;

    public function __construct($message, $protection = null)
    {
        parent::__construct($message);
        $this->protection = $protection;
    }

    public function getMessageKey()
    {
        /** @Ignore */
        return $this->message;
    }

    /**
     * @return string|null
     */
    public function getProtection()
    {
        return $this->protection;
    }
}
