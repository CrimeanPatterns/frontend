<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step\SecurityQuestion;

use AwardWallet\MainBundle\Entity\Sitegroup;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\LocationChangedChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SupportChecker;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use AwardWallet\MainBundle\Security\TwoFactorAuthentication\QuestionGenerator;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\SecurityQuestion\SupportChecker
 * @group frontend-unit
 * @group security
 */
class SupportCheckerTest extends Test
{
    use ProphecyTrait;

    public function testUserDoesntNeedSecurityQuestions()
    {
        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions(Argument::any())
            ->shouldNotBeCalled();

        $request = new Request();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info("User doesn't need security questions", Argument::type('array'))
            ->shouldBeCalledOnce();

        $credentials = (new Credentials(
            new StepData(),
            $request
        ))->setUser(new Usr());

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged($credentials, Argument::cetera())
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $checker = new SupportChecker(
            $questionGenerator->reveal(),
            $locationChangedChecker->reveal(),
            $logger->reveal()
        );

        $this->assertFalse($checker->supports($credentials, []));
    }

    public function testUserForcingGroupShouldFastTriggerCheck()
    {
        $user =
            (new Usr())
            ->addGroup(
                (new Sitegroup())
                ->setGroupname(SupportChecker::FORCE_SECURITY_QUESTIONS_GROUP_NAME)
            );
        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([1])
            ->shouldBeCalledOnce();

        $request = new Request();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info(Argument::containingString("User in forcing group "), Argument::type('array'))
            ->shouldBeCalledOnce();
        $logger
            ->info(Argument::containingString('available security question(s)'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->shouldNotBeCalled();

        $checker = new SupportChecker(
            $questionGenerator->reveal(),
            $locationChangedChecker->reveal(),
            $logger->reveal()
        );

        $credentials = (new Credentials(
            new StepData(),
            $request
        ))->setUser($user);

        $this->assertTrue($checker->supports($credentials, []));
    }

    public function testUserTestIpAddressShouldFastTriggerCheck()
    {
        $user = new Usr();

        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([1])
            ->shouldBeCalledOnce();

        $request = new Request([], [], [], [
            SupportChecker::TEST_IP_ADDRESS_COOKIE_NAME => 1,
        ]);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info(Argument::containingString('cookie'), Argument::type('array'))
            ->shouldBeCalledOnce();
        $logger
            ->info(Argument::containingString('available security question(s)'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $credentials = (new Credentials(
            new StepData(),
            $request
        ))->setUser($user);

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->shouldNotBeCalled();

        $checker = new SupportChecker(
            $questionGenerator->reveal(),
            $locationChangedChecker->reveal(),
            $logger->reveal()
        );

        $this->assertTrue($checker->supports($credentials, []));
    }

    public function testUserChangedLocationAndPasswordShouldTriggerCheck()
    {
        $user =
            (new Usr())
            ->setLastlogondatetime($changeDate = new \DateTime('-3 days'))
            ->setChangePasswordDate($changeDate)
            ->setChangePasswordMethod(Usr::CHANGE_PASSWORD_METHOD_LINK);

        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([1])
            ->shouldBeCalledOnce();

        $request = new Request();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning(Argument::containingString("User location changed"), Argument::type('array'))
            ->shouldBeCalledOnce();

        $logger
            ->warning(Argument::containingString("Required security questions for user, password changed"), Argument::type('array'))
            ->shouldBeCalledOnce();

        $logger
            ->info(Argument::containingString('available security question(s)'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $credentials = (new Credentials(
            new StepData(),
            $request
        ))->setUser($user);

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged($credentials, Argument::cetera())
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $checker = new SupportChecker(
            $questionGenerator->reveal(),
            $locationChangedChecker->reveal(),
            $logger->reveal()
        );

        $this->assertTrue($checker->supports($credentials, []));
    }

    public function testEmptySecurityQuestionsShouldNotTriggerCheck()
    {
        $user = new Usr();
        $questionGenerator = $this->prophesize(QuestionGenerator::class);
        $questionGenerator
            ->getQuestions($user)
            ->willReturn([])
            ->shouldBeCalledOnce();

        $request = new Request([], [], [], [
            SupportChecker::TEST_IP_ADDRESS_COOKIE_NAME => 1,
        ]);

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info(Argument::containingString('cookie'), Argument::type('array'))
            ->shouldBeCalledOnce();

        $logger
            ->info("User hasn't any security questions", Argument::type('array'))
            ->shouldBeCalledOnce();

        $credentials = (new Credentials(
            new StepData(),
            $request
        ))->setUser($user);

        $locationChangedChecker = $this->prophesize(LocationChangedChecker::class);
        $locationChangedChecker
            ->isLocationChanged(Argument::cetera())
            ->shouldNotBeCalled();

        $checker = new SupportChecker(
            $questionGenerator->reveal(),
            $locationChangedChecker->reveal(),
            $logger->reveal()
        );

        $this->assertFalse($checker->supports($credentials, []));
    }
}
