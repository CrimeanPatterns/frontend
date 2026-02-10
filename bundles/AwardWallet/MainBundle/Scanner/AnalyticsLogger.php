<?php

namespace AwardWallet\MainBundle\Scanner;

use AwardWallet\MainBundle\Service\GoogleAnalytics4;

class AnalyticsLogger
{
    private GoogleAnalytics4 $googleAnalytics4;

    public function __construct(GoogleAnalytics4 $googleAnalytics4)
    {
        $this->googleAnalytics4 = $googleAnalytics4;
    }

    public function logMailboxAdded(string $type, int $userid, string $platform): void
    {
        // TODO: GA4 redo
        /*
        $this->googleAnalytics
            ->setEventCategory('mailbox')
            ->setEventAction('added')
            ->setDataSource($platform)
            ->setEventLabel($type)
            ->setEventValue($userid)
            ->sendEvent();
        */
    }
}
