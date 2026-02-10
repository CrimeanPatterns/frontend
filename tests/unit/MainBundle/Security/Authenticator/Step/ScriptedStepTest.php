<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\ScriptedStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @group frontend-unit
 * @group security
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\ScriptedStep
 */
class ScriptedStepTest extends Test
{
    use ProphecyTrait;
    use ExpectStepExceptionTrait;

    public function testWhitelistedIpShouldDisableCheck()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Server has white-listed IP, skip Scripted check', Argument::type('array'))
            ->shouldBeCalledOnce();

        $logger
            ->warning(Argument::cetera())
            ->shouldNotBeCalled();

        $request = new Request([], [], [], [], [], [
            ScriptedStep::WHITELISTED_IP_SERVER_PARAMETER_NAME => '1',
        ]);
        $credentials = (new Credentials(new StepData(), $request));

        $step = new ScriptedStep($logger->reveal());
        $this->assertEquals(CheckResult::ABSTAIN, $step->check($credentials));
    }

    public function testValidCheck()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Scripted check required', Argument::type('array'))
            ->shouldBeCalledOnce();
        $logger
            ->info('Scripted check succeeded', Argument::type('array'))
            ->shouldBeCalledOnce();
        $logger
            ->warning(Argument::cetera())
            ->shouldNotBeCalled();

        $request = new Request();
        $session = $this->prophesize(SessionInterface::class);
        $session
            ->get(ScriptedStep::SESSION_VALUE_NAME, Argument::cetera())
            ->willReturn(['result' => '100'])
            ->shouldBeCalledOnce();

        $request->setSession($session->reveal());

        $credentials = (new Credentials(
            (new StepData())
                ->setScripted(100),
            $request
        ));

        $step = new ScriptedStep($logger->reveal());
        $this->assertEquals(CheckResult::SUCCESS, $step->check($credentials));
    }

    public function testInvalidCheck()
    {
        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('Scripted check required', Argument::type('array'))
            ->shouldBeCalledOnce();
        $logger
            ->warning("Scripted check failed", Argument::type('array'))
            ->shouldBeCalledOnce();

        $request = new Request();
        $session = $this->prophesize(SessionInterface::class);
        $session
            ->get(ScriptedStep::SESSION_VALUE_NAME, Argument::cetera())
            ->willReturn(['result' => '100'])
            ->shouldBeCalledOnce();

        $request->setSession($session->reveal());

        $credentials = (new Credentials(
            (new StepData())
                ->setScripted(200),
            $request
        ));

        $step = new ScriptedStep($logger->reveal());

        $this->expectStepErrorException('Bad credentials');

        $step->check($credentials);
    }
}
