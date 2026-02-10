<?php

namespace AwardWallet\MainBundle\Service\Lounge\Action;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class FreezeAction extends AbstractAction
{
    /**
     * @Serializer\Type("array")
     */
    private array $props;

    /**
     * @var string[]
     * @Serializer\Type("array")
     */
    private array $emails;

    /**
     * @Serializer\Type("bool")
     */
    private bool $sendToSlack;

    /**
     * @param string[] $props
     */
    public function __construct(array $props, array $emails, bool $sendToSlack = true)
    {
        if (\count($props) === 0) {
            throw new \InvalidArgumentException('props should not be empty');
        }

        $this->props = $props;
        $this->emails = $emails;
        $this->sendToSlack = $sendToSlack;
    }

    public function setProps(array $props): self
    {
        $this->props = $props;

        return $this;
    }

    public function getProps(): array
    {
        return $this->props;
    }

    /**
     * @return string[]
     */
    public function getEmails(): array
    {
        return $this->emails;
    }

    /**
     * @param string[] $emails
     */
    public function setEmails(array $emails): self
    {
        $this->emails = $emails;

        return $this;
    }

    public function isSendToSlack(): bool
    {
        return $this->sendToSlack;
    }

    public function setSendToSlack(bool $sendToSlack): self
    {
        $this->sendToSlack = $sendToSlack;

        return $this;
    }
}
