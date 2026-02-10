<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;

/**
 * @NoDI()
 */
class TransactionQueryCondition
{
    /** @var float */
    private $lessThan;
    /** @var float */
    private $greaterThan;
    /** @var float */
    private $exactly;

    public function __construct(?float $lessThan = 0, ?float $greaterThan = 0, ?float $exactly = 0)
    {
        $this->lessThan = $lessThan;
        $this->greaterThan = $greaterThan;
        $this->exactly = $exactly;
    }

    public function __toString()
    {
        $sign = null;
        $value = null;

        if ($this->lessThan > 0) {
            $sign = '<';
            $value = $this->lessThan;
        }

        if ($this->greaterThan > 0) {
            $sign = '>';
            $value = $this->greaterThan;
        }

        if ($this->exactly > 0) {
            $sign = '=';
            $value = $this->exactly;
        }

        return sprintf('%s %s', $sign, round($value, 2));
    }

    public static function createLessThan(float $lessThan)
    {
        return new self($lessThan);
    }

    public static function createGreaterThan(float $greaterThan)
    {
        return new self(0, $greaterThan);
    }

    public static function createExactly(float $exactly)
    {
        return new self(0, 0, $exactly);
    }

    public function getLessThan(): ?float
    {
        return $this->lessThan;
    }

    public function getGreaterThan(): ?float
    {
        return $this->greaterThan;
    }

    public function getExactly(): ?float
    {
        return $this->exactly;
    }
}
