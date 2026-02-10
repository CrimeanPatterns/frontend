<?php

namespace AwardWallet\MainBundle\Entity;

interface DateRangeInterface
{
    /**
     * @return \DateTime
     */
    public function getStartDate();

    /**
     * @return \DateTime|null
     */
    public function getEndDate();

    /**
     * @return \DateTime
     */
    public function getUTCStartDate();

    /**
     * @return \DateTime|null
     */
    public function getUTCEndDate();
}
