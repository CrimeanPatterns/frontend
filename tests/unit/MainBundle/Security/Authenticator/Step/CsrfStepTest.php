<?php

namespace AwardWallet\Tests\Unit\MainBundle\Security\Authenticator\Step;

use AwardWallet\MainBundle\Security\Authenticator\Credentials;
use AwardWallet\MainBundle\Security\Authenticator\Step\CheckResult;
use AwardWallet\MainBundle\Security\Authenticator\Step\CsrfStep;
use AwardWallet\MainBundle\Security\Authenticator\Step\StepData;
use Codeception\TestCase\Test;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * @coversDefaultClass \AwardWallet\MainBundle\Security\Authenticator\Step\CsrfStep
 * @group frontend-unit
 * @group security
 */
class CsrfStepTest extends Test
{
    use ProphecyTrait;
    use ExpectStepExceptionTrait;

    public function testShouldThrowOnInvalidToken()
    {
        $csrfToken = 'someToken';
        $tokenManager = $this->prophesize(CsrfTokenManagerInterface::class);
        $tokenManager
            ->isTokenValid(
                Argument::that(function (CsrfToken $token) use ($csrfToken) {
                    return
                        ($token->getId() === 'authenticate')
                        && ($token->getValue() === $csrfToken);
                })
            )
            ->willReturn(false)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->warning('CSRF token is invalid', Argument::type('array'))
            ->shouldBeCalledOnce();

        $step = new CsrfStep(
            $tokenManager->reveal(),
            $logger->reveal()
        );

        $this->expectStepErrorException('Invalid CSRF token');

        $step->check(new Credentials(
            (new StepData())->setCsrfToken($csrfToken),
            new Request()
        ));
    }

    public function testSuccess()
    {
        $tokenManager = $this->prophesize(CsrfTokenManagerInterface::class);
        $tokenManager
            ->isTokenValid(Argument::type(CsrfToken::class))
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $logger = $this->prophesize(LoggerInterface::class);
        $logger
            ->info('CSRF token is valid', Argument::type('array'))
            ->shouldBeCalledOnce();

        $step = new CsrfStep(
            $tokenManager->reveal(),
            $logger->reveal()
        );

        $this->assertEquals(
            CheckResult::SUCCESS,
            $step->check(new Credentials(
                (new StepData())->setCsrfToken('sometoken'),
                new Request()
            ))
        );
    }
}
