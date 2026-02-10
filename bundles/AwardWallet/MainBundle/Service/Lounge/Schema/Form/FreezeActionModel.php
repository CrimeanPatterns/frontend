<?php

namespace AwardWallet\MainBundle\Service\Lounge\Schema\Form;

use AwardWallet\MobileBundle\Form\Model\AbstractEntityAwareModel;
use Symfony\Component\Validator\Constraints as Assert;

class FreezeActionModel extends AbstractEntityAwareModel
{
    private array $props = [];

    private ?string $emails = null;

    /**
     * @Assert\NotBlank
     */
    private ?\DateTime $deleteDate = null;

    public function getProps(): array
    {
        return $this->props;
    }

    public function setProps(array $props): self
    {
        $this->props = $props;

        return $this;
    }

    public function getEmails(): ?string
    {
        return $this->emails;
    }

    public function setEmails(?string $emails): self
    {
        $this->emails = $emails;

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
