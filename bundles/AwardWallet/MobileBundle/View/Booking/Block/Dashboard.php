<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class Dashboard extends AbstractBlock
{
    /**
     * @var int
     */
    public $active = 0;

    /**
     * @var int|null
     */
    public $lastUnread;

    public function __construct()
    {
    }

    /**
     * @param int $active
     * @return Dashboard
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @param int|null $lastUnread last unread abrequest id
     * @return Dashboard
     */
    public function setLastUnread($lastUnread)
    {
        $this->lastUnread = $lastUnread;

        return $this;
    }
}
