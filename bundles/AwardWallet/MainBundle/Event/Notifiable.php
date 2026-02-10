<?php

namespace AwardWallet\MainBundle\Event;

trait Notifiable
{
    protected $notified = false;

    /**
     * @return bool
     */
    public function isNotified()
    {
        return $this->notified;
    }

    /**
     * @param bool $notified
     * @return Notifiable
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;

        return $this;
    }
}
