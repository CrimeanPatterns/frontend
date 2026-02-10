<?php

namespace AwardWallet\Test\Unit\MobileBilling;

use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Service\InAppPurchase\AppleAppStore\Provider as AppleStore;
use AwardWallet\MainBundle\Service\InAppPurchase\GooglePlay\Provider as GooglePlay;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderRegistry;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Symfony\Component\HttpFoundation\Request;

/**
 * @group mobile
 * @group frontend-unit
 * @group mobile/billing
 * @group billing
 */
class ProviderRegistryTest extends BaseContainerTest
{
    /**
     * @var ProviderRegistry
     */
    private $registry;

    public function _before()
    {
        parent::_before();
        $this->registry = $this->container->get(ProviderRegistry::class);
    }

    public function _after()
    {
        $this->registry = null;
        parent::_after();
    }

    public function testBillingPlatform()
    {
        $request = new Request();
        $request->headers->replace([MobileHeaders::MOBILE_PLATFORM => "android"]);
        $this->assertInstanceOf(GooglePlay::class, $this->registry->detectProvider($request));

        $request->headers->replace([MobileHeaders::MOBILE_PLATFORM => "ios"]);
        $this->assertInstanceOf(AppleStore::class, $this->registry->detectProvider($request));

        $request->headers->replace([MobileHeaders::MOBILE_PLATFORM => "xxx"]);
        $this->assertNull($this->registry->detectProvider($request));

        $request->headers->replace([]);
        $this->assertNull($this->registry->detectProvider($request));
    }

    public function testUnknownPlatformException()
    {
        $this->expectException(\AwardWallet\MainBundle\Service\InAppPurchase\Exception\UnknownPlatformException::class);
        $this->registry->detectProvider(new Request(), true);
    }
}
