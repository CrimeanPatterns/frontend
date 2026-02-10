<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\Lounge\Action\AbstractAction;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="LoungeAction")
 * @ORM\Entity
 */
class LoungeAction
{
    /**
     * @var int
     * @ORM\Column(name="LoungeActionID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var Lounge
     * @ORM\ManyToOne(targetEntity="Lounge")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="LoungeID", referencedColumnName="LoungeID")
     * })
     */
    private $lounge;

    /**
     * @var AbstractAction
     * @ORM\Column(name="Action", type="jms_json", nullable=false)
     */
    private $action;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $updateDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="DeleteDate", type="datetime", nullable=true)
     */
    private $deleteDate;

    public function __construct()
    {
        $this->createDate = new \DateTime();
        $this->updateDate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLounge(): ?Lounge
    {
        return $this->lounge;
    }

    public function setLounge(?Lounge $lounge): self
    {
        $this->lounge = $lounge;

        return $this;
    }

    public function getAction(): ?AbstractAction
    {
        return $this->action;
    }

    public function setAction(AbstractAction $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    public function getUpdateDate(): ?\DateTimeInterface
    {
        return $this->updateDate;
    }

    public function setUpdateDate(\DateTimeInterface $updateDate): self
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    public function getDeleteDate(): ?\DateTime
    {
        return $this->deleteDate;
    }

    public function setDeleteDate(?\DateTime $deleteDate): self
    {
        $this->deleteDate = $deleteDate;

        return $this;
    }
}
