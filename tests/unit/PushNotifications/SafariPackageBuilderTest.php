<?php

namespace AwardWallet\Tests\Unit\PushNotifications;

use AwardWallet\MainBundle\Service\WebPush\SafariPackageBuilder;
use AwardWallet\Tests\Unit\BaseContainerTest;

/**
 * @group push
 * @group frontend-unit
 */
class SafariPackageBuilderTest extends BaseContainerTest
{
    public function testBuild()
    {
        $builder = $this->container->get(SafariPackageBuilder::class);
        $file = $builder->build(7, $this->container->getParameter("host"));
        $this->assertTrue(file_exists($file));
    }
}
