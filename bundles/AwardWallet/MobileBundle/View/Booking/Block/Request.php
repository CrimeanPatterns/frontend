<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;
use AwardWallet\MobileBundle\View\Date;
use AwardWallet\MobileBundle\View\DateFormatted;

class Request extends AbstractBlock
{
    /** @var int */
    public $id;

    /** @var string */
    public $bookerIcon;

    /** @var bool */
    public $active = true;

    /** @var Date */
    public $lastUpdateDate;

    /** @var bool */
    public $newMessage = false;

    /** @var string */
    public $listTitle;

    /** @var string */
    public $statusIcon;

    /** @var string */
    public $status;

    /** @var int */
    public $statusCode;

    /** @var string */
    public $bookerName;

    /** @var string */
    public $kindIcon = 'icon-plane';

    /** @var Date */
    public $startDate;

    public $details;
    /**
     * @var Message[]
     */
    public $messages;

    /**
     * @var array
     */
    public $channels;

    /**
     * @var string
     */
    public $contactName;

    /**
     * @var int
     */
    public $contactUid;

    public function __construct()
    {
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @param string $bookerIcon
     */
    public function setBookerIcon($bookerIcon)
    {
        $this->bookerIcon = $bookerIcon;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @param Date|DateFormatted $lastUpdateDate
     */
    public function setLastUpdateDate($lastUpdateDate)
    {
        $this->lastUpdateDate = $lastUpdateDate;
    }

    /**
     * @param bool $newMessage
     */
    public function setNewMessage($newMessage)
    {
        $this->newMessage = $newMessage;
    }

    /**
     * @param string $listTitle
     */
    public function setListTitle($listTitle)
    {
        $this->listTitle = $listTitle;
    }

    /**
     * @param string $statusIcon
     */
    public function setStatusIcon($statusIcon)
    {
        $this->statusIcon = $statusIcon;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }

    /**
     * @param string $bookerName
     */
    public function setBookerName($bookerName)
    {
        $this->bookerName = $bookerName;
    }

    /**
     * @param string $kindIcon
     */
    public function setKindIcon($kindIcon)
    {
        $this->kindIcon = $kindIcon;
    }

    /**
     * @param Date $startDate
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
    }

    public function addRow($row)
    {
        if (!is_array($this->details)) {
            $this->details = [];
        }
        $this->details[] = $row;
    }

    /**
     * @return Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     * @return $this
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @return Request
     */
    public function setDetails($details)
    {
        $this->details = $details;

        return $this;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function setChannels(array $channels): Request
    {
        $this->channels = $channels;

        return $this;
    }

    public function getContactName(): string
    {
        return $this->contactName;
    }

    public function setContactName(string $contactName): Request
    {
        $this->contactName = $contactName;

        return $this;
    }

    public function getContactUid(): int
    {
        return $this->contactUid;
    }

    public function setContactUid(int $contactUid): Request
    {
        $this->contactUid = $contactUid;

        return $this;
    }
}
