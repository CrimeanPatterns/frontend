<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class AppleUserInfo
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $sub;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $lastName;

    /**
     * @var string
     * @ORM\Column(type="string", nullable=false)
     */
    private $email;

    /**
     * @var string
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $createDate;

    /**
     * @var string
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $updateDate;

    public function __construct(string $sub, string $firstName, string $lastName, string $email)
    {
        $this->sub = $sub;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->createDate = new \DateTimeImmutable();
        $this->updateDate = new \DateTimeImmutable();
    }

    public function update(string $firstName, string $lastName, string $email): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->updateDate = new \DateTimeImmutable();
    }

    public function markAsUsed(): void
    {
        $this->updateDate = new \DateTimeImmutable();
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}
