<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="Outbox")
 * @ORM\Entity()
 */
class Outbox
{
    public const TYPE_USR_LAST_LOGON_POINT = 1;
    public const TYPE_USERIP_POINT = 2;
    public const TYPE_USERIP_POINT_INITIAL_IMPORT = 3;
    public const TYPE_USR_LAST_LOGON_POINT_INITIAL_IMPORT = 4;

    /**
     * @ORM\Column(name="OutboxID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected ?int $id;
    /**
     * @ORM\Column(name="TypeID", type="string", length=60, nullable=false)
     */
    protected ?int $type;
    /**
     * @ORM\Column(name="Payload", type="json", nullable=false)
     */
    protected $payload;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }
}
