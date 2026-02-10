<?php

namespace AwardWallet\MainBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Mobilefeedback.
 *
 * @ORM\Table(name="MobileFeedback", indexes={@ORM\Index(name="idx_MobileFeedback_UserID", columns={"UserID"})})
 * @ORM\Entity
 */
class Mobilefeedback
{
    public const ACTION_SKIP = 1;
    public const ACTION_CONTACTUS = 2;
    public const ACTION_RATE = 3;

    /**
     * @var int
     * @ORM\Column(name="MobileFeedbackID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $mobilefeedbackid;

    /**
     * @var int
     * @ORM\Column(name="Action", type="integer", nullable=false)
     */
    protected $action;

    /**
     * @var \DateTime
     * @ORM\Column(name="Date", type="datetime", nullable=false)
     */
    protected $date;

    /**
     * @var string
     * @ORM\Column(name="AppVersion", type="string", length=20, nullable=false)
     */
    protected $appversion;

    /**
     * @var \Usr
     * @ORM\ManyToOne(targetEntity="Usr")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="UserID", referencedColumnName="UserID")
     * })
     */
    protected $userid;

    private static $actions = [
        self::ACTION_SKIP,
        self::ACTION_CONTACTUS,
        self::ACTION_RATE,
    ];

    /**
     * @return int
     */
    public function getMobilefeedbackid()
    {
        return $this->mobilefeedbackid;
    }

    /**
     * @return int
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return string
     */
    public function getAppversion()
    {
        return $this->appversion;
    }

    /**
     * @return \Usr
     */
    public function getUser()
    {
        return $this->userid;
    }

    /**
     * @param int $action
     * @return Mobilefeedback
     */
    public function setAction($action)
    {
        if (!self::isValidAction($action)) {
            throw new \InvalidArgumentException('Undefined action type');
        }

        $this->action = $action;

        return $this;
    }

    /**
     * @param \DateTime $date
     * @return Mobilefeedback
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @param string $appversion
     * @return Mobilefeedback
     */
    public function setAppversion($appversion)
    {
        $this->appversion = $appversion;

        return $this;
    }

    /**
     * @return Mobilefeedback
     */
    public function setUser(Usr $userid)
    {
        $this->userid = $userid;

        return $this;
    }

    /**
     * @param int $action
     * @return bool
     */
    public static function isValidAction($action)
    {
        return in_array($action, self::$actions, true);
    }
}
