<?php

namespace AwardWallet\MainBundle\Form\Model;

use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

class UserConnectionModel extends AbstractEntityAwareModel
{
    /**
     * @var ?int
     * @Assert\NotBlank
     */
    private $sharebydefault;

    /**
     * @var ?int
     * @Assert\NotBlank
     */
    private $accesslevel;

    /**
     * @var ?int
     * @Assert\NotBlank
     */
    private $tripsharebydefault;

    /**
     * @var ?int
     * @Assert\NotBlank
     */
    private $tripAccessLevel;

    /**
     * @var array[]
     */
    private $sharedTimelines;

    public function getSharebydefault(): ?int
    {
        return $this->sharebydefault;
    }

    public function setSharebydefault(?int $sharebydefault): UserConnectionModel
    {
        $this->sharebydefault = $sharebydefault;

        return $this;
    }

    public function getAccesslevel(): ?int
    {
        return $this->accesslevel;
    }

    public function setAccesslevel(?int $accesslevel): UserConnectionModel
    {
        $this->accesslevel = $accesslevel;

        return $this;
    }

    public function getTripsharebydefault(): ?int
    {
        return $this->tripsharebydefault;
    }

    public function setTripsharebydefault(?int $tripsharebydefault): UserConnectionModel
    {
        $this->tripsharebydefault = $tripsharebydefault;

        return $this;
    }

    public function getTripAccessLevel(): ?int
    {
        return $this->tripAccessLevel;
    }

    public function setTripAccessLevel(?int $tripAccessLevel): UserConnectionModel
    {
        $this->tripAccessLevel = $tripAccessLevel;

        return $this;
    }

    /**
     * @return array[]
     */
    public function getSharedTimelines(): array
    {
        return $this->sharedTimelines;
    }

    /**
     * @param array[] $sharedTimelines
     */
    public function setSharedTimelines(array $sharedTimelines): UserConnectionModel
    {
        $this->sharedTimelines = $sharedTimelines;

        return $this;
    }
}
