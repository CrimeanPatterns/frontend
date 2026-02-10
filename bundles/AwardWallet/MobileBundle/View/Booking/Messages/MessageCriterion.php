<?php

namespace AwardWallet\MobileBundle\View\Booking\Messages;

use AwardWallet\MainBundle\Entity\AbRequest;
use AwardWallet\MainBundle\Entity\Usr;

class MessageCriterion
{
    public const FLAG_LOAD_LAST_UNREAD = 1;
    public const FLAG_LOAD_CHUNK = 2;

    /**
     * @var int
     */
    public $requestId;

    /**
     * @var int
     */
    public $lowerMessageId;

    /**
     * @var int
     */
    public $upperMessageId;

    /**
     * @var bool
     */
    public $loadAutoReply = false;

    /**
     * @var AbRequest
     */
    public $request;

    /**
     * @var int
     */
    public $flags = 0;

    /**
     * values:
     *     false - no read marks
     *     \DateTime - last read date.
     *
     * @var \DateTime[]|false[]
     */
    public $lastReadByUser = [];

    /**
     * @var int[]
     */
    public $messageVersions;
    /**
     * @var Usr
     */
    public $viewer;

    public function __construct(AbRequest $request, Usr $viewer)
    {
        $this->request = $request;
        $this->requestId = $request->getAbRequestID();
        $this->viewer = $viewer;
    }

    /**
     * @param int $lowerMessageId
     * @return MessageCriterion
     */
    public function setLowerMessageId($lowerMessageId)
    {
        $this->lowerMessageId = $lowerMessageId;

        return $this;
    }

    /**
     * @param int $upperMessageId
     * @return MessageCriterion
     */
    public function setUpperMessageId($upperMessageId)
    {
        $this->upperMessageId = $upperMessageId;

        return $this;
    }

    /**
     * @param bool $loadAutoReply
     * @return $this
     */
    public function setLoadAutoReply($loadAutoReply)
    {
        $this->loadAutoReply = $loadAutoReply;

        return $this;
    }

    /**
     * @param int $flags
     * @return MessageCriterion
     */
    public function setFlags($flags)
    {
        $this->flags = $flags;

        return $this;
    }

    /**
     * @param \int[] $messageVersions
     * @return MessageCriterion
     */
    public function setMessageVersions($messageVersions)
    {
        $this->messageVersions = $messageVersions;

        return $this;
    }
}
