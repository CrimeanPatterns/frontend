<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Entity\Repositories\OwnerRepository;
use Doctrine\ORM\Mapping as ORM;

trait OwnableTrait
{
    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @var Useragent
     * @ORM\ManyToOne(targetEntity="Useragent")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserAgentID", referencedColumnName="UserAgentID")
     * })
     */
    protected $userAgent;

    public function getOwner(): ?Owner
    {
        if (null === $this->user) {
            return null;
        }

        return OwnerRepository::getOwner($this->user, $this->userAgent);
    }

    /**
     * @return $this
     */
    public function setOwner(?Owner $owner = null)
    {
        if (null === $owner) {
            $this->user = null;
            $this->userAgent = null;

            return $this;
        }

        $this->user = $owner->getUser();
        $this->userAgent = $owner->getFamilyMember();

        return $this;
    }

    public function getOwnerId(): string
    {
        if ($this->user === null) {
            $result = "User:null";
        } else {
            $result = "User:{$this->user->getUserid()}";
        }

        if ($this->userAgent === null) {
            $result .= ",UserAgent:null";
        } else {
            $result .= ",UserAgent:{$this->userAgent->getUseragentid()}";
        }

        return $result;
    }
}
