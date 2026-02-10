<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Globals\StringHandler;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="OneTimeCode")
 * @ORM\Entity
 */
class OneTimeCode
{
    /**
     * @var int
     * @ORM\Column(name="OneTimeCodeID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="Code", type="string", length=6, nullable=false)
     */
    protected $code;

    /**
     * @var Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $user;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreationDate", type="datetime", nullable=false)
     */
    protected $creationDate;

    public function __construct()
    {
        $this->code = StringHandler::getRandomString(ord('0'), ord('9'), 6);
        $this->creationDate = new \DateTime();
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    public function setUser(Usr $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * get code age in seconds.
     *
     * @return int
     */
    public function getAge()
    {
        return time() - $this->creationDate->getTimestamp();
    }

    public function getCreationDate(): \DateTime
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTime $creationDate): OneTimeCode
    {
        $this->creationDate = $creationDate;

        return $this;
    }
}
