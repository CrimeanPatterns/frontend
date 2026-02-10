<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\LocationChangedChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\SupportChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\OneTimeCodeByEmail\SupportChecker
 * @group frontend-unit
 * @group security
 */
class SupportCheckerTest extends Test
{
    use ProphecyTrait;

    public function testUserInBypassingGroup()
    {
        $user =
            (new Usr())
            ->addGroup(
                (new Sitegroup())
                ->setGroupname(SupportChecker::BYPASS_EMAIL_OTC_GROUP)
            );

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info(Argument::containingString('User in bypassing group "'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->shouldNotBeCalled();

        $checker = new SupportChecker(
            $locationChangedChecker->reveal(),
            $logger->reveal(),
            true
        );

        $credentials = (new Credentials(
            new StepData(),
            new Request()
        ))->setUser($user);

        $this->assertFalse($checker->supports($credentials, []));
    }

    public function testUserInBypassingroup()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info(Argument::containingString('cookie'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->shouldNotBeCalled();

        $checker = new SupportChecker(
            $locationChangedChecker->reveal(),
            $logger->reveal(),
            true
        );

        $credentials = (new Credentials(
            new StepData(),
            new Request([], [], [], [
                SupportChecker::TEST_IP_ADDRESS_COOKIE_NAME => '1',
            ])
        ))->setUser(new Usr());

        $this->assertTrue($checker->supports($credentials, []));
    }

    public function testCompletelyDisablingCheck()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('email otc step is disabled', Argument::type('array'))
            ->shouldBeCalledOnce();

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->shouldNotBeCalled();

        $checker = new SupportChecker(
            $locationChangedChecker->reveal(),
            $logger->reveal(),
            false
        );

        $credentials = (new Credentials(
            new StepData(),
            new Request()
        ))->setUser(new Usr());

        $this->assertFalse($checker->supports($credentials, []));
    }

    public function testForceEnablingOtcAndLocationChangedCheck()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('Required email-OTC check for user, location changed', Argument::type('array'))
            ->shouldBeCalledOnce();

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $checker = new SupportChecker(
            $locationChangedChecker->reveal(),
            $logger->reveal(),
            false
        );

        $credentials = (new Credentials(
            new StepData(),
            new Request([], [], [], [
                SupportChecker::ENABLE_EMAIL_OTC_COOKIE_NAME => '1',
            ])
        ))->setUser(new Usr());

        $this->assertTrue($checker->supports($credentials, []));
    }

    public function testLocationNotChanged()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Email OTC check skipped', Argument::type('array'))
            ->shouldBeCalledOnce();

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $checker = new SupportChecker(
            $locationChangedChecker->reveal(),
            $logger->reveal(),
            true
        );

        $credentials = (new Credentials(
            new StepData(),
            new Request()
        ))->setUser(new Usr());

        $this->assertFalse($checker->supports($credentials, []));
    }
}
