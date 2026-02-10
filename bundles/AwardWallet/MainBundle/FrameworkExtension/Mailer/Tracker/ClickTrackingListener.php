<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Mailer\Tracker;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class ClickTrackingListener
{
    /**
     * @var ClickTracker
     */
    private $clickTracker;

    public function __construct(ClickTracker $clickTracker)
    {
        $this->clickTracker = $clickTracker;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();

        $trackingId = $request->query->get("emtr");

        if (
            ($trackingId === null)
            || ($request->getMethod() !== Request::METHOD_GET)
            || !ClickTracker::isValidTrackingId($trackingId)
        ) {
            return;
        }

        $this->clickTracker->trackClickUnencodedId($trackingId, $request->getRequestUri(), $request);
    }
}
