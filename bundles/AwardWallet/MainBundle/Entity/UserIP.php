<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="UserIP")
 * @ORM\Entity()
 */
class UserIP
{
    /**
     * @ORM\Column(name="UserIPID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $userIPID;
    /**
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumn(name="UserID", referencedColumnName="UserID", nullable=false)
     */
    protected ?Usr $userID;
    /**
     * @ORM\Column(name="IP", type="string", length=60, nullable=false)
     */
    protected ?string $ip;
    /**
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected ?\DateTime $updateDate;

    public function getUserIPID(): ?int
    {
        return $this->userIPID;
    }

    public function getId(): ?int
    {
        return $this->userIPID;
    }

    public function getUser(): ?Usr
    {
        return $this->userID;
    }

    public function setUser(Usr $user): self
    {
        $this->userID = $user;

        return $this;
    }

    public function getUpdateDate(): ?\DateTime
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTime $updateDate): void
    {
        $this->updateDate = $updateDate;
    }

    public function getIp(): ?string
    {
        return $this->ip;
    }

    public function setIp(string $ip): self
    {
        $this->ip = $ip;

        return $this;
    }
}
