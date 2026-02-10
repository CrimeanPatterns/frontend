<?php

namespace AwardWallet\Tests\Unit\Checker;

use AwardWallet\Tests\Unit\BaseContainerTest;
use Psr\Log\LoggerInterface;

class UserEmulatorTest extends BaseContainerTest
{
    public const UNKNOWN_IP = '127.0.0.1'; // not known to geoip
    public const US_IP = '198.199.104.203';
    public const RU_IP = '46.146.27.134';

    /**
     * @var \UserEmulator
     */
    private $userEmulator;

    /**
     * @var \UserInfo
     */
    private $localUser;

    /**
     * @var \UserInfo
     */
    private $usUser;

    /**
     * @var \UserInfo
     */
    private $ruUser;

    public function _before()
    {
        parent::_before();

        $this->container->get("database_connection")->executeUpdate("delete from UserInfo");

        $this->userEmulator = new \UserEmulator(
            $this->container->get("database_connection"),
            $this->container->get("aw.geoip.city"),
            $this->container->get(LoggerInterface::class)
        );

        $this->localUser = $this->userEmulator->getUser(self::UNKNOWN_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->userEmulator->unlock($this->localUser->id);
        $this->usUser = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->userEmulator->unlock($this->usUser->id);
        $this->ruUser = $this->userEmulator->getUser(self::RU_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->userEmulator->unlock($this->ruUser->id);
    }

    public function testNoCountry()
    {
        $user = $this->userEmulator->getUser(self::UNKNOWN_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->localUser, $user);
    }

    public function testCountry()
    {
        $user = $this->userEmulator->getUser(self::RU_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->ruUser, $user);
    }

    public function testState()
    {
        $virginiaUser = $this->userEmulator->getUser("23.23.183.188", \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertNotEquals($this->usUser->id, $virginiaUser->id);
        $this->userEmulator->unlock($virginiaUser->id);

        $user = $this->userEmulator->getUser("23.23.183.188", \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($virginiaUser->id, $user->id);
    }

    public function testLocking()
    {
        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->usUser, $user);

        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertNotEquals($this->usUser->id, $user->id);
    }

    public function testCookies()
    {
        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->usUser, $user);
        $this->userEmulator->saveCookies($user->id, ["blah" => "vah"]);
        $this->userEmulator->unlock($user->id);

        $user = $this->userEmulator->getUser(self::RU_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->ruUser, $user);
        $this->userEmulator->saveCookies($user->id, ["ляля" => "3 рубля"]);
        $this->userEmulator->unlock($user->id);

        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->usUser->id, $user->id);
        $this->assertEquals(["blah" => "vah"], $user->cookies);

        $user = $this->userEmulator->getUser(self::RU_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 0);
        $this->assertEquals($this->ruUser->id, $user->id);
        $this->assertEquals(["ляля" => "3 рубля"], $user->cookies);
    }

    public function testBrowser()
    {
        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_CHROME, 'Firefox 46', 0);
        $this->assertNotEquals($this->usUser->id, $user->id);
    }

    public function testShift()
    {
        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Firefox 46', 1);
        $this->assertNotEquals($this->usUser->id, $user->id);
    }

    public function testUserAgent()
    {
        $user = $this->userEmulator->getUser(self::US_IP, \UserEmulator::BROWSER_FIREFOX, 'Some superfox', 0);
        $this->assertEquals($this->usUser->id, $user->id);
        $this->assertEquals("Firefox 46", $user->userAgent);
    }
}
