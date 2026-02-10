<?php

namespace AwardWallet\MainBundle\Service\TravelSummary\Formatter\Tip;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;

/**
 * @NoDI
 */
class User
{
    private Usr $user;

    private ?Useragent $userAgent;

    public function __construct(Usr $user, ?Useragent $userAgent)
    {
        $this->user = $user;
        $this->userAgent = $userAgent;
    }

    public function isAgent(): bool
    {
        return !is_null($this->userAgent);
    }

    public function getUserName(): string
    {
        return $this->isAgent() ? $this->userAgent->getFullName() : $this->user->getFullName();
    }
}
