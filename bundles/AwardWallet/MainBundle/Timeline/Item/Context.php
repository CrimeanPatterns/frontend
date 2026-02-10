<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Service\ItineraryFormatter\Formatter\SimpleFormatterInterface;

class Context
{
    private ?SimpleFormatterInterface $propFormatter = null;

    /**
     * todo: other solution, https://redmine.awardwallet.com/issues/15141.
     */
    private ?\DateTime $prevStartDate = null;

    public function setPropFormatter(SimpleFormatterInterface $formatter)
    {
        $this->propFormatter = $formatter;
    }

    public function getPropFormatter(): SimpleFormatterInterface
    {
        return $this->propFormatter;
    }

    public function getPrevStartDate(): ?\DateTime
    {
        return $this->prevStartDate;
    }

    public function setPrevStartDate(?\DateTime $prevStartDate)
    {
        $this->prevStartDate = $prevStartDate;
    }
}
