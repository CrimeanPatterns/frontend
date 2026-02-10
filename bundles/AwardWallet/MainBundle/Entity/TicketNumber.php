<?php
/**
 * Created by PhpStorm.
 * User: ANeklyudov
 * Date: 31/05/2018
 * Time: 11:10.
 */

namespace AwardWallet\MainBundle\Entity;

class TicketNumber
{
    /**
     * @var string
     */
    private $number;

    /**
     * @var bool
     */
    private $masked = false;

    public function __construct(string $number, bool $masked = false)
    {
        $this->number = $number;
        $this->masked = $masked;
    }

    public function __toString(): string
    {
        return $this->number;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function setNumber(string $number): void
    {
        $this->number = $number;
    }

    public function isMasked(): bool
    {
        return $this->masked;
    }

    public function setMasked(bool $masked): void
    {
        $this->masked = $masked;
    }
}
