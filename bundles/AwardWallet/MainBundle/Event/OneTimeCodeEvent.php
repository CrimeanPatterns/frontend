<?php

namespace AwardWallet\MainBundle\Event;

use AwardWallet\MainBundle\Entity\Usr;
use Symfony\Component\EventDispatcher\Event;

class OneTimeCodeEvent extends Event
{
    use Notifiable;

    public const NAME = 'aw.security.one_time_code';
    /**
     * @var Usr
     */
    private $user;

    private $oneTimeCode;

    /**
     * OneTimeCodeEvent constructor.
     */
    public function __construct(Usr $user, $oneTimeCode)
    {
        $this->user = $user;
        $this->oneTimeCode = $oneTimeCode;
    }

    /**
     * @return Usr
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param Usr $user
     * @return OneTimeCodeEvent
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function getOneTimeCode()
    {
        return $this->oneTimeCode;
    }

    /**
     * @return OneTimeCodeEvent
     */
    public function setOneTimeCode($oneTimeCode)
    {
        $this->oneTimeCode = $oneTimeCode;

        return $this;
    }
}
