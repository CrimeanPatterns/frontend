<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * VerifiedEmail.
 *
 * @ORM\Table(name="VerifiedEmail")
 * @ORM\Entity
 */
class VerifiedEmail
{
    /**
     * @var int
     * @ORM\Column(name="VerifiedEmailID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     * @Assert\NotBlank(groups={"register", "change_email"})
     * @Assert\Regex("/^[_a-zA-Z\d\-\+\.]+@([_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+)$/i", message="user.email.invalid", groups={"register", "change_email"})
     * @ORM\Column(name="Email", type="string", length=80, unique=true)
     */
    private $email;

    /**
     * @var \DateTime
     * @ORM\Column(name="VerificationDate", type="datetime")
     */
    private $verificationDate;

    public function __construct(string $email, ?\DateTime $verificationDate)
    {
        $this->email = $email;
        $this->verificationDate = $verificationDate ?? new \DateTime();
    }

    /**
     * Set verificationDate.
     *
     * @param \DateTime $verificationDate
     */
    public function setVerificationDate($verificationDate): VerifiedEmail
    {
        $this->verificationDate = $verificationDate;

        return $this;
    }
}
