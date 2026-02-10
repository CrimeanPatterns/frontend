<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class Tripit extends AbstractSource
{
    public const SOURCE_ID = 'tripit';

    /**
     * @var \DateTimeInterface
     * @Serializer\Type("DateTimeImmutable")
     */
    private $date;
    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $email;

    public function __construct(?\DateTimeInterface $date, ?string $email)
    {
        parent::__construct();
        $this->date = $date;
        $this->email = $email;
    }

    public function getId(): string
    {
        return self::SOURCE_ID;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function getOldId(): ?string
    {
        return null;
    }
}
