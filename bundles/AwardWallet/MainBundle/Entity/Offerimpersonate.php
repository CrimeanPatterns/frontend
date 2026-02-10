<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Offerimpersonate.
 *
 * @ORM\Table(name="OfferImpersonate")
 * @ORM\Entity
 */
class Offerimpersonate
{
    /**
     * @var int
     * @ORM\Column(name="OfferImpersonateID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $offerimpersonateid;

    /**
     * @var string
     * @ORM\Column(name="Login", type="string", length=30, nullable=false)
     */
    protected $login;

    /**
     * @var bool
     * @ORM\Column(name="Disabled", type="boolean", nullable=true)
     */
    protected $disabled = true;

    /**
     * Get offerimpersonateid.
     *
     * @return int
     */
    public function getOfferimpersonateid()
    {
        return $this->offerimpersonateid;
    }

    /**
     * Set login.
     *
     * @param string $login
     * @return Offerimpersonate
     */
    public function setLogin($login)
    {
        $this->login = $login;

        return $this;
    }

    /**
     * Get login.
     *
     * @return string
     */
    public function getLogin()
    {
        return $this->login;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     * @return Offerimpersonate
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }
}
