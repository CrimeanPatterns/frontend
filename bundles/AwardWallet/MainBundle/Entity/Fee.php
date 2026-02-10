<?php
/**
 * Created by PhpStorm.
 * User: ANelyudov
 * Date: 26.03.18
 * Time: 12:43.
 */

namespace AwardWallet\MainBundle\Entity;

class Fee
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $charge;

    /**
     * Fee constructor.
     */
    public function __construct(string $name, float $charge)
    {
        $this->name = $name;
        $this->charge = $charge;
    }

    /**
     * @return string
     *
     * Name: Charge
     */
    public function __toString(): string
    {
        return "{$this->getName()}: {$this->getCharge()}";
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCharge(): float
    {
        return $this->charge;
    }
}
