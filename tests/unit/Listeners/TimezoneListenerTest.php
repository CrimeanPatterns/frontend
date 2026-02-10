<?php

namespace AwardWallet\Tests\Unit\Listeners;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\TimezoneListener;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\Tests\Unit\BaseTest;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @group frontend-unit
 */
class TimezoneListenerTest extends BaseTest
{
    public function validTimezoneDataProvider()
    {
        return [
            ['Asia/Yekaterinburg', 'Asia/Yekaterinburg'],
            ['5', '+05:00'],
            ['-5', '-05:00'],
            ['0', '+00:00'],
        ];
    }

    /**
     * @dataProvider validTimezoneDataProvider
     */
    public function testValidTimezoneShouldBeSetInLocalizer($timezoneHeader, $timezoneName)
    {
        $localizeService = $this->prophesize(LocalizeService::class);
        $localizeService->setTimezone(Argument::that(function ($timezone) use ($timezoneName) {
            return
                $timezone instanceof \DateTimeZone
                && $timezone->getName() === $timezoneName;
        }))->shouldBeCalled();

        $listener = new TimezoneListener($localizeService->reveal());
        $request = new Request();
        $request->headers->set('Accept-Timezone', $timezoneHeader);

        $listener->onKernelRequest($this->getResponseEvent($request));
    }

    public function testInvalidTimezoneShouldBeSkipped()
    {
        $localizeService = $this->prophesize(LocalizeService::class);
        $localizeService->setTimezone()->shouldNotBeCalled();

        $listener = new TimezoneListener($localizeService->reveal());
        $request = new Request();
        $request->headers->set('Accept-Timezone', 'Asia/Blablainvalid');

        $listener->onKernelRequest($this->getResponseEvent($request));
    }

    protected function getResponseEvent($request)
    {
        return new GetResponseEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);
    }
}
