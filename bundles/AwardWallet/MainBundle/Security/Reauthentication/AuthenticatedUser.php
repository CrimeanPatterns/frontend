<?php

namespace AwardWallet\MainBundle\Security\Reauthentication;

use AwardWallet\MainBundle\Entity\Usr;

class AuthenticatedUser
{
    /**
     * @var Usr
     */
    private $entity;

    /**
     * @var bool
     */
    private $isBusiness;

    public function __construct(Usr $entity, bool $isBusiness = false)
    {
        $this->entity = $entity;
        $this->isBusiness = $isBusiness;
    }

    public function getEntity(): Usr
    {
        return $this->entity;
    }

    public function isBusiness(): bool
    {
        return $this->isBusiness;
    }
}
