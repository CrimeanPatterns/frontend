<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\MainBundle\Service\Notification\Content;
use Doctrine\ORM\Mapping as ORM;

/**
 * NotificationTemplate.
 *
 * @ORM\Table(name="NotificationTemplate")
 * @ORM\Entity(repositoryClass="AwardWallet\MainBundle\Entity\Repositories\NotificationTemplateRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class NotificationTemplate
{
    public const DELIVERY_MODE_DEFAULT = 0;
    public const DELIVERY_MODE_MOBILE = 1;
    public const DELIVERY_MODE_DESKTOP = 2;
    public const DELIVERY_MODE_MOBILE_AND_DESKTOP = 3;

    public const STATE_NEW = 0;
    public const STATE_TESTED = 1;
    public const STATE_SENDING = 2;
    public const STATE_DONE = 3;

    public const DEFAULT_TTL = 24 * 3600;

    /**
     * @var int
     * @ORM\Column(name="NotificationTemplateID", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $NotificationTemplateID;

    /**
     * @var string
     * @ORM\Column(name="Title", type="string", length=100, nullable=false)
     */
    protected $Title;

    /**
     * @var string
     * @ORM\Column(name="Message", type="text", nullable=false)
     */
    protected $Message;

    /**
     * @var int
     * @ORM\Column(name="Type", type="integer", nullable=false)
     */
    protected $type = Content::TYPE_OFFER;

    /**
     * @var string
     * @ORM\Column(name="Link", type="string", length=1000, nullable=false)
     */
    protected $Link;

    /**
     * @var \DateTime
     * @ORM\Column(name="TTL", type="datetime", nullable=false)
     */
    protected $TTL;

    /**
     * @var bool
     * @ORM\Column(name="AutoClose")
     */
    protected $autoClose;

    /**
     * @var array
     * @ORM\Column(name="UserGroups", type="json_array")
     */
    protected $UserGroups = [];

    /**
     * @var int
     * @ORM\Column(name="DeliveryMode", type="integer", nullable=false)
     */
    protected $DeliveryMode = self::DELIVERY_MODE_DEFAULT;

    /**
     * @var int
     * @ORM\Column(name="State", type="integer", nullable=false)
     */
    protected $State = self::STATE_NEW;

    /**
     * @var \DateTime
     * @ORM\Column(name="CreateDate", type="datetime", nullable=false)
     */
    protected $CreateDate;

    /**
     * @var \DateTime
     * @ORM\Column(name="UpdateDate", type="datetime", nullable=false)
     */
    protected $UpdateDate;

    /**
     * @var int
     * @ORM\Column(name="QueueStat", type="integer", nullable=false)
     */
    protected $QueueStat = 0;

    /**
     * @var int
     * @ORM\Column(name="SendStat", type="integer", nullable=false)
     */
    protected $SendStat = 0;

    /**
     * NotificationTemplate constructor.
     */
    public function __construct()
    {
        $this->TTL = new \DateTime('+' . self::DEFAULT_TTL . ' seconds');
    }

    /**
     * @return int
     */
    public function getNotificationTemplateID()
    {
        return $this->NotificationTemplateID;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->Title;
    }

    /**
     * @param string $Title
     */
    public function setTitle($Title)
    {
        $this->Title = $Title;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->Message;
    }

    /**
     * @param string $Message
     */
    public function setMessage($Message)
    {
        $this->Message = $Message;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return NotificationTemplate
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->Link;
    }

    /**
     * @param string $Link
     */
    public function setLink($Link)
    {
        $this->Link = $Link;
    }

    /**
     * @return \DateTime
     */
    public function getTTL()
    {
        return $this->TTL;
    }

    /**
     * @return $this
     */
    public function setTTL(\DateTime $TTL)
    {
        $this->TTL = $TTL;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAutoClose()
    {
        return $this->autoClose;
    }

    /**
     * @param bool $autoClose
     * @return NotificationTemplate
     */
    public function setAutoClose($autoClose)
    {
        $this->autoClose = $autoClose;

        return $this;
    }

    /**
     * @return array
     */
    public function getUserGroups()
    {
        return $this->UserGroups;
    }

    /**
     * @param array $UserGroups
     */
    public function setUserGroups($UserGroups)
    {
        $this->UserGroups = array_map('strtolower', array_map('trim', array_values($UserGroups)));
    }

    public function addUserGroup($UserGroup)
    {
        $UserGroup = strtolower(trim($UserGroup));

        if (!in_array($UserGroup, $this->UserGroups)) {
            $this->UserGroups[] = $UserGroup;
        }
    }

    /**
     * @return int
     */
    public function getDeliveryMode()
    {
        return $this->DeliveryMode;
    }

    /**
     * @param int $DeliveryMode
     */
    public function setDeliveryMode($DeliveryMode)
    {
        $this->DeliveryMode = $DeliveryMode;
    }

    /**
     * @return int
     */
    public function getState()
    {
        return $this->State;
    }

    /**
     * @param int $State
     */
    public function setState($State)
    {
        $this->State = $State;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->CreateDate;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->UpdateDate;
    }

    /**
     * @return int
     */
    public function getQueueStat()
    {
        return $this->QueueStat;
    }

    /**
     * @param int $QueueStat
     */
    public function setQueueStat($QueueStat)
    {
        $this->QueueStat = $QueueStat;
    }

    /**
     * @return int
     */
    public function getSendStat()
    {
        return $this->SendStat;
    }

    /**
     * @param int $SendStat
     */
    public function setSendStat($SendStat)
    {
        $this->SendStat = $SendStat;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function prePersist()
    {
        if (empty($this->CreateDate)) {
            $this->CreateDate = new \DateTime();
        }
        $this->UpdateDate = new \DateTime();
    }

    public function getLogName()
    {
        return date("Y_m_d_") .

                preg_replace("/\s/", "", ucwords(preg_replace("/[^\w]/", " ", $this->getTitle())))

            . ".log";
    }
}
