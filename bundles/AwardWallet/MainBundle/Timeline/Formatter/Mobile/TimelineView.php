<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile;

use AwardWallet\MainBundle\Globals\JsonSerialize;
use AwardWallet\MainBundle\Timeline\Item;

class TimelineView implements \JsonSerializable
{
    use JsonSerialize\FilterNull;
    use JsonSerialize\NonRecursive;

    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $familyName;

    /**
     * @var array
     */
    public $items;

    /**
     * @var int
     */
    public $userAgentId;

    /**
     * @var string
     */
    public $itineraryForwardEmail;

    /**
     * @var bool
     */
    public $needMore;

    /**
     * @var bool
     */
    public $canChange = false;
    /**
     * @var bool
     */
    public $canConnectMailbox = false;

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param Item\ItemInterface[] $items
     * @return $this
     */
    public function setItems($items)
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param int $userAgentId
     * @return $this
     */
    public function setUserAgentId($userAgentId)
    {
        $this->userAgentId = $userAgentId;

        return $this;
    }

    /**
     * @param string $itineraryForwardEmail
     * @return $this
     */
    public function setItineraryForwardEmail($itineraryForwardEmail)
    {
        $this->itineraryForwardEmail = $itineraryForwardEmail;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNeedMore()
    {
        return $this->needMore;
    }

    /**
     * @param bool $needMore
     * @return $this
     */
    public function setNeedMore($needMore)
    {
        $this->needMore = $needMore;

        return $this;
    }

    /**
     * @return string
     */
    public function getFamilyName()
    {
        return $this->familyName;
    }

    /**
     * @param string $familyName
     * @return TimelineView
     */
    public function setFamilyName($familyName)
    {
        $this->familyName = $familyName;

        return $this;
    }

    public function isCanChange(): bool
    {
        return $this->canChange;
    }

    public function setCanChange(bool $canChange): TimelineView
    {
        $this->canChange = $canChange;

        return $this;
    }

    public function canConnectMailbox(): bool
    {
        return $this->canConnectMailbox;
    }

    public function setCanConnectMailbox(bool $canConnectMailbox): TimelineView
    {
        $this->canConnectMailbox = $canConnectMailbox;

        return $this;
    }
}
