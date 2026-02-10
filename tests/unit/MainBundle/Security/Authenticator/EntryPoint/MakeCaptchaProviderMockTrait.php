<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\EntryPoint;

use AwardWallet\MainBundle\Security\Captcha\Provider\CaptchaProviderInterface;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;

trait MakeCaptchaProviderMockTrait
{
    protected function makeCaptchaProviderMock(
        ?string $siteKey = null,
        ?string $scriptUrl = null,
        ?string $vendor = null
    ): ObjectProphecy {
        return $this->prophesize(CaptchaProviderInterface::class)
            ->getSiteKey()
            ->willReturn($siteKey ?? 'somekey')
            ->getObjectProphecy()

            ->getScriptUrl(Argument::any())
            ->willReturn($scriptUrl ?? 'somescripturl')
            ->getObjectProphecy()

            ->getVendor()
            ->willReturn($vendor ?? 'somevendor')
            ->getObjectProphecy();
    }
}
