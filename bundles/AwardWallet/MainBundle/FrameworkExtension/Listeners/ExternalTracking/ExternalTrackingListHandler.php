<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners\ExternalTracking;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\MobileDeviceListener;
use Symfony\Component\HttpFoundation\RequestStack;

class ExternalTrackingListHandler
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function add(ExternalTrackingInterface $externalTracking): void
    {
        $request = $this->requestStack->getMasterRequest();

        if (!$request) {
            return;
        }

        if ($request->attributes->has(MobileDeviceListener::EXTERNAL_TRACKING_ATTRIBUTE)) {
            $attributesList = $request->attributes->get(MobileDeviceListener::EXTERNAL_TRACKING_ATTRIBUTE);
        } else {
            $attributesList = [];
        }

        $attributesList[] = $externalTracking;
        $request->attributes->set(MobileDeviceListener::EXTERNAL_TRACKING_ATTRIBUTE, $attributesList);
    }
}
