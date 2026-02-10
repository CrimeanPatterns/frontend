<?php

namespace AwardWallet\MainBundle\Updater;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class ProgressInfo
{
    /**
     * @var bool
     */
    private $isError;
    /**
     * @var string
     */
    private $message;
    /**
     * @var int
     */
    private $code;
    /**
     * @var array
     */
    private $itineraryCodes;

    /**
     * @param string[] $itineraryCodes - like ['R.123', 'L.456', .. ]
     */
    public function __construct(bool $isError, ?string $message = null, ?int $code = null, array $itineraryCodes = [])
    {
        $this->isError = $isError;
        $this->message = $message;
        $this->code = $code;
        $this->itineraryCodes = $itineraryCodes;
    }

    public function isError(): bool
    {
        return $this->isError;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getCode(): ?int
    {
        return $this->code;
    }

    public function getItineraryCodes(): array
    {
        return $this->itineraryCodes;
    }
}
