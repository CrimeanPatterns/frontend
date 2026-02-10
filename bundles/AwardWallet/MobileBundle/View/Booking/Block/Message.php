<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MainBundle\Entity\AbMessage;
use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;
use AwardWallet\MobileBundle\View\AbstractBlock;
use AwardWallet\MobileBundle\View\Booking\Block\Message\ChangeStatusRequest;
use AwardWallet\MobileBundle\View\Booking\Block\Message\Invoice;
use AwardWallet\MobileBundle\View\Booking\Block\Message\InvoicePaid;
use AwardWallet\MobileBundle\View\Booking\Block\Message\SeatAssignments;
use AwardWallet\MobileBundle\View\Booking\Block\Message\ShareAccountsRequest;
use AwardWallet\MobileBundle\View\Booking\Block\Message\ShareAccountsResponse;
use AwardWallet\MobileBundle\View\Booking\Block\Message\UpdateRequest;
use AwardWallet\MobileBundle\View\Booking\Block\Message\UserText;
use AwardWallet\MobileBundle\View\Booking\Block\Message\WriteCheck;
use AwardWallet\MobileBundle\View\Booking\Block\Message\Ycb;
use AwardWallet\MobileBundle\View\Date;
use AwardWallet\MobileBundle\View\DateFormatted;

class Message extends AbstractBlock
{
    use FilterNull;
    public const BOX_INCOME = 'in';
    public const BOX_OUTCOME = 'out';

    /**
     * @var string
     */
    public $body;
    /**
     * @var Date|DateFormatted
     */
    public $date;
    /**
     * @var string
     */
    public $box;
    /**
     * @var int
     */
    public $id;
    /**
     * @var string
     */
    public $author;
    /**
     * base64 encoded image.
     *
     * @var string
     */
    public $avatar;
    /**
     * @var bool
     */
    public $readed;
    /**
     * @var Date|DateFormatted
     */
    public $lastUpdate;
    /**
     * @var DateFormatted
     */
    public $requestUpdateDate;
    /**
     * @var int
     */
    public $internalDate;
    /**
     * @var bool
     */
    public $hidden = false;
    /**
     * @var bool
     */
    public $canEdit = false;
    /**
     * @var bool
     */
    public $canDelete = false;

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * @return Date|DateFormatted
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param Date|DateFormatted $date
     * @return Message
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return string
     */
    public function getBox()
    {
        return $this->box;
    }

    /**
     * @param string $box
     * @return Message
     */
    public function setBox($box)
    {
        $this->box = $box;

        return $this;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Message
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return Message
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param string $avatar
     * @return Message
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @return bool
     */
    public function isReaded()
    {
        return $this->readed;
    }

    /**
     * @param bool $readed
     * @return $this
     */
    public function setReaded($readed)
    {
        $this->readed = $readed;

        return $this;
    }

    /**
     * @return Date
     */
    public function getLastUpdate()
    {
        return $this->lastUpdate;
    }

    /**
     * @param Date|DateFormatted $date
     * @return Message
     */
    public function setLastUpdate($date)
    {
        if (!is_null($date)) {
            $this->lastUpdate = $date;
        }

        return $this;
    }

    /**
     * @return DateFormatted
     */
    public function getRequestUpdateDate()
    {
        return $this->requestUpdateDate;
    }

    /**
     * @param DateFormatted $requestUpdateDate
     * @return Message
     */
    public function setRequestUpdateDate($requestUpdateDate)
    {
        $this->requestUpdateDate = $requestUpdateDate;

        return $this;
    }

    public function isHidden()
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return int
     */
    public function getInternalDate()
    {
        return $this->internalDate;
    }

    /**
     * @param int|null
     * @return $this
     */
    public function setInternalDate($internalDate)
    {
        $this->internalDate = $internalDate;

        return $this;
    }

    /**
     * @param int $timestamp
     * @return $this
     */
    public function setInternalDateTimestamp($timestamp)
    {
        $this->internalDate = $timestamp;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCanEdit()
    {
        return $this->canEdit;
    }

    /**
     * @param bool $canEdit
     * @return Message
     */
    public function setCanEdit($canEdit)
    {
        $this->canEdit = $canEdit;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCanDelete()
    {
        return $this->canDelete;
    }

    /**
     * @param bool $canDelete
     * @return Message
     */
    public function setCanDelete($canDelete)
    {
        $this->canDelete = $canDelete;

        return $this;
    }

    /**
     * @return Message
     */
    public static function create(AbMessage $message)
    {
        if ($message->isInvoice()) {
            return new Invoice();
        } elseif ($message->isShareRequest()) {
            return new ShareAccountsRequest();
        } elseif ($message->isShareResponse()) {
            return new ShareAccountsResponse();
        } elseif ($message->isSeatAssignments()) {
            return new SeatAssignments();
        } elseif ($message->isUserText()) {
            return new UserText();
        } elseif ($message->isYcbMessage()) {
            return new Ycb();
        } elseif ($message->getType() === AbMessage::TYPE_UPDATE_REQUEST) {
            return new UpdateRequest();
        } elseif ($message->getType() === AbMessage::TYPE_STATUS_REQUEST) {
            return new ChangeStatusRequest();
        } elseif ($message->getType() === AbMessage::TYPE_INVOICE_PAID) {
            return new InvoicePaid();
        } elseif ($message->getType() === AbMessage::TYPE_WRITE_CHECK) {
            return new WriteCheck();
        }

        return new self();
    }
}
