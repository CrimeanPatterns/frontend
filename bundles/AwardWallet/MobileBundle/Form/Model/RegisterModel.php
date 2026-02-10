<?php

namespace AwardWallet\MobileBundle\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterModel
{
    /**
     * @var \AwardWallet\MainBundle\Entity\Usr
     */
    protected $user;

    /**
     * @var bool
     * @Assert\NotBlank(groups={"register"})
     * @Assert\IsTrue(message = "login.agree.error")
     */
    protected $agree;

    public function setUser(\AwardWallet\MainBundle\Entity\Usr $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setAgree($agree)
    {
        $this->agree = (bool) $agree;
    }

    public function getAgree()
    {
        return $this->agree;
    }
}
