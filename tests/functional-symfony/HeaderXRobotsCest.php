<?php

namespace AwardWallet\Tests\FunctionalSymfony;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * @group frontend-functional
 */
class HeaderXRobotsCest
{
    private const HEADER_NAME = 'x-robots-tag';
    private const XROBOTS_STATE_DISALLOW = 'noindex, nofollow';

    private ?RouterInterface $router;
    private ?\HttpBrowser $httpBrowser;

    public function _before(\TestSymfonyGuy $I)
    {
        /** @var RouterInterface $router */
        $this->router = $I->grabService(RouterInterface::class);
        $curlDriver = $I->grabService(\HttpDriverInterface::class);
        $this->httpBrowser = new \HttpBrowser('none', $curlDriver);
    }

    // indexing is now prohibited in /robots.txt
    public function testNormalState(\TestSymfonyGuy $I): void
    {
        $vars = ['AnyVar', 'q'];

        foreach ($vars as $varName) {
            $url = $this->router->generate('aw_home', [$varName => mt_rand()], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->httpBrowser->GetURL($url);

            $xrobots = $http->Response['headers'][self::HEADER_NAME] ?? '';
            !is_array($xrobots) ? $xrobots = [$xrobots] : null; // test runner with noindex

            $I->assertFalse(in_array(self::XROBOTS_STATE_DISALLOW, $xrobots));
        }
    }

    public function testExpectNoindex(\TestSymfonyGuy $I): void
    {
        // see nginx conf
        $noindexVars = ['BackTo', 'KeepDesktop', 'Abuse', 'Code']; // 'mobile',

        foreach ($noindexVars as $varName) {
            $url = $this->router->generate('aw_home', [$varName => mt_rand()], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->httpBrowser->GetURL($url);

            $xrobots = $this->httpBrowser->Response['headers'][self::HEADER_NAME] ?? null;

            $I->assertIsArray($xrobots);
            $I->assertTrue(in_array(self::XROBOTS_STATE_DISALLOW, $xrobots));
        }
    }
}
