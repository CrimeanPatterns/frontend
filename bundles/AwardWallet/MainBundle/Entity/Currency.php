<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Currency.
 *
 * @ORM\Table(name="Currency")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\CurrencyRepository")
 */
class Currency
{
    public const MILES_ID = 1;
    public const POINTS_ID = 2;
    public const USD_ID = 3;
    public const EURO_ID = 4;
    public const KILOMETERS_ID = 41;

    /**
     * @var int
     * @ORM\Column(name="CurrencyID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $currencyid;

    /**
     * @var string
     * @ORM\Column(name="Name", type="string", length=250, nullable=false)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(name="Plural", type="string", length=250, nullable=false)
     */
    protected $plural;

    /**
     * @var string
     * @ORM\Column(name="Sign", type="string", length=20, nullable=true)
     */
    protected $sign;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=20, nullable=true)
     */
    protected $code;

    /**
     * Get currencyid.
     *
     * @return int
     */
    public function getCurrencyid()
    {
        return $this->currencyid;
    }

    /**
     * Set name.
     *
     * @param string $name
     * @return Currency
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getPlural(): string
    {
        return $this->plural;
    }

    public function setPlural(string $plural): Currency
    {
        $this->plural = $plural;

        return $this;
    }

    /**
     * Set sign.
     *
     * @param string $sign
     * @return Currency
     */
    public function setSign($sign)
    {
        $this->sign = $sign;

        return $this;
    }

    /**
     * Get sign.
     *
     * @return string
     */
    public function getSign()
    {
        return $this->sign;
    }

    /**
     * Set code.
     *
     * @param string $code
     * @return Currency
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
}
