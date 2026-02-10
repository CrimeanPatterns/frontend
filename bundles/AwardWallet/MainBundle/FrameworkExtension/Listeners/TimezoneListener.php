<?php

namespace AwardWallet\MainBundle\FrameworkExtension\Listeners;

use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

class TimezoneListener
{
    public const REQUEST_ATTR_NAME = '_timezone';
    /**
     * @var LocalizeService
     */
    private $localizeService;

    public function __construct(LocalizeService $localizeService)
    {
        $this->localizeService = $localizeService;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (StringUtils::isEmpty($timezoneHeader = $request->headers->get('Accept-Timezone'))) {
            return;
        }

        try {
            $timezone = new \DateTimeZone(
                is_numeric($timezoneHeader) ?
                    ($timezoneHeader >= 0 ? "GMT+{$timezoneHeader}" : "GMT{$timezoneHeader}") :
                    $timezoneHeader
            );
        } catch (\Exception $e) {
            return;
        }

        $request->attributes->set(self::REQUEST_ATTR_NAME, $timezone);
        $this->localizeService->setTimezone($timezone);
    }
}
