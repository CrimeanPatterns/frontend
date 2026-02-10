<?php

namespace AwardWallet\MainBundle\Email;

class ParsedEmailSource
{
    public const SOURCE_UNKNOWN = 0;
    public const SOURCE_PLANS = 1; // <userlogin>@awardwallet.com (plans@awardwallet.com)
    public const SOURCE_SCANNER = 2; // connected mailbox

    /**
     * @var int
     */
    private $source;

    /**
     * @var string
     */
    private $email;

    /** @var string */
    private $emailRequestId;

    private bool $isGpt;

    public function __construct(int $source, ?string $email, ?string $emailRequestId = null, bool $isGpt = false)
    {
        $this->source = $source;
        $this->email = $email;
        $this->emailRequestId = $emailRequestId;
        $this->isGpt = $isGpt;
    }

    public function getSource(): int
    {
        return $this->source;
    }

    public function getUserEmail(): ?string
    {
        return $this->email;
    }

    public function getEmailRequestId(): ?string
    {
        return $this->emailRequestId;
    }

    public function isGpt(): bool
    {
        return $this->isGpt;
    }
}
