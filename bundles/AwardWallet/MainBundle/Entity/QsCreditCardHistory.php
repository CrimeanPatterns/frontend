<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="QsCreditCardHistory")
 */
class QsCreditCardHistory
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(name="QsCreditCardHistoryID", type="integer", nullable=false)
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var QsCreditCard
     * @ORM\ManyToOne(targetEntity="AwardWallet\MainBundle\Entity\QsCreditCard")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="QsCreditCardID", referencedColumnName="QsCreditCardID")
     * })
     */
    private $qsCreditCard;

    /**
     * @var string
     * @ORM\Column(name="CardName", type="string", length=255, nullable=false)
     */
    private $cardName;

    /**
     * @var string
     * @ORM\Column(name="BonusMilesFull", type="text", nullable=true)
     */
    private $bonusMilesFull;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    private $creationDate;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return $this
     */
    public function setQsCreditCard(QsCreditCard $card)
    {
        $this->qsCreditCard = $card;

        return $this;
    }

    public function getQsCreditCard(): QsCreditCard
    {
        return $this->qsCreditCard;
    }

    /**
     * @return $this
     */
    public function setCardName(string $cardName): self
    {
        $this->cardName = $cardName;

        return $this;
    }

    public function getCardName(): string
    {
        return $this->cardName;
    }

    /**
     * @return $this
     */
    public function setBonusMilesFull(string $bonusMilesFull): self
    {
        $this->bonusMilesFull = $bonusMilesFull;

        return $this;
    }

    public function getBonusMilesFull(): ?string
    {
        return $this->bonusMilesFull;
    }

    /**
     * @return $this
     */
    public function setCreationDate(\DateTime $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }
}
