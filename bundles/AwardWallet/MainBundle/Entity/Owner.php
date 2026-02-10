<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;

class Owner
{
    /**
     * @var Usr
     */
    private $user;

    /**
     * @var Useragent
     */
    private $familyMember;

    /**
     * @var Owner[]
     */
    private static $registry = [];

    /**
     * Owner constructor.
     */
    public function __construct(Usr $user, ?Useragent $userAgent = null)
    {
        $this->user = $user;
        $this->familyMember = $userAgent;
    }

    public function getUser(): Usr
    {
        return $this->user;
    }

    /**
     * @return Useragent
     */
    public function getFamilyMember()
    {
        return $this->familyMember;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        if (null !== $this->familyMember) {
            return $this->familyMember->getFullName();
        } else {
            return $this->user->getFullName();
        }
    }

    /**
     * @return bool
     */
    public function isFamilyMember()
    {
        return null !== $this->familyMember;
    }

    /**
     * Check whether the owner belongs to the same family
     * (same owners are considered to be part of the same family).
     *
     * @return bool
     */
    public function isFamilyMemberOf(Owner $owner)
    {
        return $this->user === $owner->user;
    }

    /**
     * Check if this owner is a family member of the user.
     *
     * @return bool
     */
    public function isFamilyMemberOfUser(Usr $user)
    {
        return $this->isFamilyMemberOf(OwnerRepository::getOwner($user));
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        if ($this->isFamilyMember()) {
            return $this->familyMember->getEmail();
        } else {
            return $this->user->getEmail();
        }
    }

    /**
     * @return string|null
     */
    public function getItineraryForwardingEmail()
    {
        if ($this->isFamilyMember()) {
            return $this->familyMember->getItineraryForwardingEmail();
        } else {
            return $this->user->getItineraryForwardingEmail();
        }
    }

    /**
     * @return Useragent|null
     */
    public function getUseragentForUser(Usr $user)
    {
        $isDirectOwner = $user === $this->getUser();

        if (!$isDirectOwner) {
            $connection = $user->getConnectionWith($this->getUser());

            if (null === $connection) {
                throw new \InvalidArgumentException("No connection found with provided user");
            }

            return $this->isFamilyMember() ? $this->getFamilyMember() : $connection;
        } else {
            return $this->isFamilyMember() ? $this->getFamilyMember() : null;
        }
    }

    /**
     * @return bool
     */
    public function isSame(Owner $owner)
    {
        if ($this->getUser() === $owner->getUser() && $this->getFamilyMember() === $owner->getFamilyMember()) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getIdentityString()
    {
        if ($this->isFamilyMember()) {
            return $this->user->getId() . '_' . $this->familyMember->getId();
        } else {
            return (string) $this->user->getId();
        }
    }

    public function isBusiness(): bool
    {
        return $this->user->isBusiness();
    }

    public function getOwnerId(): string
    {
        if ($this->user === null) {
            $result = "User:null";
        } else {
            $result = "User:{$this->user->getId()}";
        }

        if ($this->familyMember === null) {
            $result .= ",UserAgent:null";
        } else {
            $result .= ",UserAgent:{$this->familyMember->getId()}";
        }

        return $result;
    }
}
