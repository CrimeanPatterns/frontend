<?php

namespace AwardWallet\MainBundle\Loyalty\AccountSaving\Sources;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Email\ParsedEmailSource;
use JMS\Serializer\Annotation as Serializer;

/**
 * @NoDI()
 */
class Email extends AbstractSource
{
    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $messageId;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $requestId;

    /**
     * @var int
     * @Serializer\Type("int")
     */
    private $from;

    /**
     * @var string
     * @Serializer\Type("string")
     */
    private $recipient;

    /**
     * @var \DateTimeInterface
     * @Serializer\Type("DateTimeImmutable")
     */
    private $date;

    /**
     * @Serializer\Type("bool")
     */
    private bool $isGpt = false;

    public function __construct(string $messageId, ?string $requestId, ?ParsedEmailSource $parsedEmailSource, ?\DateTimeInterface $date)
    {
        parent::__construct();
        $this->messageId = $messageId;
        $this->requestId = $requestId;

        if (is_null($parsedEmailSource)) {
            $this->from = ParsedEmailSource::SOURCE_UNKNOWN;
        } else {
            $this->from = $parsedEmailSource->getSource();
            $this->recipient = $parsedEmailSource->getUserEmail();
        }
        $this->date = $date;
        $this->isGpt = isset($parsedEmailSource) && $parsedEmailSource->isGpt();
    }

    public function getId(): string
    {
        return "e." . $this->messageId;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    /**
     * used in setSoourceId, deprecated
     * should be deleted after migrations and release.
     *
     * @TODO: delete after release
     */
    public function getOldId(): ?string
    {
        return $this->messageId;
    }

    public function getRecipient(): ?string
    {
        return $this->recipient;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function isGpt(): bool
    {
        return $this->isGpt;
    }
}
